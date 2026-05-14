<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CartController manages the shopping cart lifecycle, including adding,
 * updating, removing, and clearing items.
 *
 * It leverages the CartManager service to handle session-based storage
 * and pricing logic.
 */
class CartController extends Controller
{
    /**
     * @param  CartManager  $cart  The service responsible for cart operations.
     */
    public function __construct(
        private readonly CartManager $cart
    ) {}

    /**
     * Display the cart page with all current items.
     *
     * @return View The rendered cart view.
     */
    public function index(): View
    {
        return view('cart', [
            'items' => $this->cart->getItems(),
            'total' => $this->cart->total(),
            'count' => $this->cart->count(),
        ]);
    }

    /**
     * Add a product to the cart.
     *
     * This method validates the input, calculates the total price including
     * quantity-based discounts and additional costs for print placements.
     *
     * @param  Request  $request  The incoming request with product details.
     * @return RedirectResponse Redirects back to the cart page with a success message.
     */
    public function add(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'color_id' => 'nullable|integer',
            'color_name' => 'nullable|string',
            'size_id' => 'nullable|integer',
            'size_name' => 'nullable|string',
            'print_placements' => 'nullable|string', // JSON encoded list of placement IDs
            'product_name' => 'required|string',
            'product_slug' => 'required|string',
            'image_url' => 'nullable|string',
        ]);

        // Decode print_placements JSON if provided
        $printPlacements = [];
        if (! empty($validated['print_placements'])) {
            $printPlacements = json_decode((string) $validated['print_placements'], true) ?? [];
        }

        // 1. Compute base unit price with quantity discounts
        $product = Product::findOrFail((int) $validated['product_id']);
        $quantity = (int) $validated['quantity'];
        $unitPrice = $product->getPriceForQuantity($quantity);

        // 2. Calculate additional cost for selected print placements
        $additionalPrice = 0;
        if (! empty($printPlacements)) {
            $additionalPrice = $product->printPlacements()
                ->whereIn('print_placements.id', $printPlacements)
                ->sum('print_placement_product.additional_price');
        }

        $finalPrice = $unitPrice + $additionalPrice;

        $this->cart->add([
            'product_id' => $validated['product_id'],
            'product_name' => $validated['product_name'],
            'product_slug' => $validated['product_slug'],
            'image_url' => $validated['image_url'] ?? null,
            'color_id' => $validated['color_id'] ?? null,
            'color_name' => $validated['color_name'] ?? null,
            'size_id' => $validated['size_id'] ?? null,
            'size_name' => $validated['size_name'] ?? null,
            'print_placements' => $printPlacements,
            'price' => $finalPrice,
            'quantity' => $quantity,
        ]);

        return redirect()->route('cart')->with('success', 'Prodotto aggiunto al carrello!');
    }

    /**
     * Update the quantity of a specific item in the cart.
     *
     * @param  Request  $request  The request containing the item key and new quantity.
     * @return RedirectResponse Redirects back to the current page.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'key' => 'required|string',
            'quantity' => 'nullable|integer|min:0',
            'size_id' => 'nullable|integer',
            'update_type' => 'nullable|string',
        ]);

        $key = $request->input('key');
        $items = app(CartManager::class)->getItems();
        $item = $items[$key] ?? null;

        if (! $item) {
            return back()->with('error', 'Item not found in cart.');
        }

        if ($request->input('update_type') === 'size') {
            $sizeId = $request->input('size_id');
            $qty = (int) $request->input('quantity');

            if (! isset($item['quantities'])) {
                $item['quantities'] = [];
            }

            $item['quantities'][$sizeId] = $qty;

            // Recalculate total quantity for the job
            $item['quantity'] = array_sum($item['quantities']);
        } else {
            // Legacy single-size update
            $item['quantity'] = (int) $request->input('quantity');
        }

        // If total quantity is 0 or less, remove the whole job
        if ($item['quantity'] <= 0) {
            $this->cart->remove($key);

            return back()->with('success', 'Prodotto rimosso dal carrello!');
        }

        // Update the item in the cart
        $this->cart->replace($key, $item);

        return back()->with('success', 'Carrello aggiornato!');
    }

    /**
     * Remove a single item from the cart.
     *
     * @param  Request  $request  The request containing the item key.
     * @return RedirectResponse Redirects back to the current page.
     */
    public function remove(Request $request): RedirectResponse
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $this->cart->remove($request->input('key'));

        return back()->with('success', 'Prodotto rimosso dal carrello!');
    }

    /**
     * Remove multiple items from the cart.
     *
     * @param  Request  $request  The request containing an array of item keys.
     * @return RedirectResponse Redirects back to the current page.
     */
    public function removeMultiple(Request $request): RedirectResponse
    {
        $keys = $request->input('keys', []);

        if (! empty($keys)) {
            $this->cart->removeMultiple($keys);
        }

        return back()->with('success', 'Prodotto rimosso dal carrello!');
    }

    /**
     * Completely empty the shopping cart.
     *
     * @return RedirectResponse Redirects back to the current page.
     */
    public function clear(): RedirectResponse
    {
        $this->cart->clear();

        return back()->with('success', 'Carrello svuotato!');
    }

    /**
     * Return a real-time price calculation for a product configuration.
     *
     * This is used by AJAX requests on the product page to show the total
     * price based on quantity and selected print placements.
     *
     * @param  Request  $request  The request containing configuration details.
     * @return JsonResponse A JSON response with unit price, total price, and discount status.
     */
    public function price(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'color_id' => 'nullable|integer',
            'size_id' => 'nullable|integer',
            'print_placements' => 'nullable|string',
        ]);

        $product = Product::findOrFail((int) $request->input('product_id'));
        $quantity = (int) $request->input('quantity');

        $unitPrice = $product->getPriceForQuantity($quantity);

        $additionalPrice = 0;
        if (! empty($request->input('print_placements'))) {
            $placementIds = json_decode((string) $request->input('print_placements'), true) ?? [];
            $additionalPrice = $product->printPlacements()
                ->whereIn('print_placements.id', $placementIds)
                ->sum('print_placement_product.additional_price');
        }

        $totalPrice = ($unitPrice * $quantity) + ($additionalPrice * $quantity);

        return response()->json([
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'quantity' => $quantity,
            'discount_applied' => $unitPrice < $product->price,
        ]);
    }
}
