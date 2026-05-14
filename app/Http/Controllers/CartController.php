<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Cart\StoreCartRequest;
use App\Http\Requests\Cart\UpdateCartRequest;
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
     * @param  StoreCartRequest  $request  The incoming request with product details.
     * @return RedirectResponse Redirects back to the cart page with a success message.
     */
    public function add(StoreCartRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $printPlacements = json_decode((string) ($validated['print_placements'] ?? '[]'), true) ?? [];

        $product = Product::findOrFail((int) $validated['product_id']);
        $quantity = (int) $validated['quantity'];

        $this->cart->add(array_merge($validated, [
            'print_placements' => $printPlacements,
            'price' => $product->calculateFinalUnitPrice($quantity, $printPlacements),
            'quantity' => $quantity,
        ]));

        return redirect()->route('cart')->with('success', 'Prodotto aggiunto al carrello!');
    }

    /**
     * Update the quantity of a specific item in the cart.
     *
     * @param  UpdateCartRequest  $request  The request containing the item key and new quantity.
     * @return RedirectResponse Redirects back to the current page.
     */
    public function update(UpdateCartRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->cart->updateItemQuantity(
            $validated['key'],
            (int) ($validated['quantity'] ?? 0),
            ($validated['update_type'] ?? null) === 'size' ? (int) ($validated['size_id'] ?? 0) : null
        );

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
        $request->validate(['key' => 'required|string']);

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
        $this->cart->removeMultiple($request->input('keys', []));

        return back()->with('success', 'Prodotti rimossi dal carrello!');
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
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'print_placements' => 'nullable|string',
        ]);

        $product = Product::findOrFail((int) $validated['product_id']);
        $quantity = (int) $validated['quantity'];
        $placements = json_decode((string) ($validated['print_placements'] ?? '[]'), true) ?? [];

        $unitPrice = $product->getPriceForQuantity($quantity);
        $finalUnitPrice = $product->calculateFinalUnitPrice($quantity, $placements);

        return response()->json([
            'unit_price' => $unitPrice,
            'total_price' => $finalUnitPrice * $quantity,
            'quantity' => $quantity,
            'discount_applied' => $unitPrice < $product->price,
        ]);
    }
}
