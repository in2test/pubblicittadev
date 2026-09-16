<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ProductClass;
use App\Http\Requests\Cart\StoreCartRequest;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\VariationOption;
use App\Models\VariationType;
use App\Services\CartManager;
use App\Services\CartPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * CartController manages the shopping cart lifecycle, including adding,
 * updating, removing, and clearing items.
 *
 * It leverages the CartManager service to handle session-based storage
 * and pricing logic. This separation of concerns allows the controller to focus
 * on HTTP requests while CartManager handles the domain logic of cart state.
 */
class CartController extends Controller
{
    /**
     * Create a new CartController instance.
     *
     * @param  CartManager  $cart  The cart manager instance for session-based cart operations.
     * @param  CartPresenter  $presenter  The cart presenter instance for view enrichment.
     */
    public function __construct(
        private readonly CartManager $cart,
        private readonly CartPresenter $presenter
    ) {}

    /**
     * Display the cart page with all current items.
     *
     * @return View Returns the cart view with enriched items, totals, and savings.
     */
    public function index(): View
    {
        return view('cart', $this->presenter->present());
    }

    /**
     * Add a product configuration to the cart.
     *
     * Business Logic:
     * When a user submits the "Aggiungi al carrello" form on a product page, this method
     * decodes their configuration (selected sizes, colors, print sides, and custom dimensions).
     * It relies on `Product::calculateTotalPrice()` to determine the exact total cost of this
     * configuration. Finally, it delegates saving the item into the session via `CartManager::add()`.
     *
     * Note: Every add creates a unique Job UUID, meaning two identical additions will result
     * in two separate items in the cart (to allow for different customer-provided files later).
     *
     * @param  StoreCartRequest  $request  Validated request containing the selected options.
     * @return RedirectResponse Redirects back to the cart with a success message.
     */
    public function add(StoreCartRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $product = Product::findOrFail((int) $validated['product_id']);
        $quantity = (int) $validated['quantity'];

        $width = isset($validated['width']) ? (float) $validated['width'] : null;
        $height = isset($validated['height']) ? (float) $validated['height'] : null;

        $totalPrice = $product->calculateTotalPrice(
            $quantity,
            $validated['quantities'] ?? [],
            $width,
            $height,
            $request->input('selected_options') ?? []
        );

        $this->cart->add(array_merge($validated, [
            'selected_options' => $request->input('selected_options') ?? [],
            'price' => $quantity > 0 ? $totalPrice / $quantity : 0.0,
            'quantity' => $quantity,
        ]));

        return redirect()->route('cart')->with('success', 'Prodotto aggiunto al carrello!');
    }

    /**
     * Remove the specified item from the cart.
     *
     * @param  Request  $request  Request containing the Job UUID to remove.
     * @return RedirectResponse Redirects back to the cart.
     */
    public function remove(Request $request): RedirectResponse
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $this->cart->remove($request->input('key'));

        return back()->with('success', 'Lavorazione rimossa dal carrello!');
    }

    /**
     * Remove multiple items from the cart.
     *
     * @param  Request  $request  Request containing an array of Job UUIDs ('keys') to remove.
     * @return RedirectResponse Redirects back to the cart with a success message.
     */
    public function removeMultiple(Request $request): RedirectResponse
    {
        $request->validate([
            'keys' => 'required|array',
            'keys.*' => 'required|string',
        ]);

        $this->cart->removeMultiple($request->input('keys'));

        return back()->with('success', 'Lavorazioni rimosse dal carrello!');
    }

    /**
     * Update the quantity of a specific item in the cart.
     *
     * Supports both global quantity update and size-specific updates for products with multiple sizes.
     *
     * @param  Request  $request  Request containing the Job UUID ('key'), new quantity, and optional sku_id.
     * @return RedirectResponse Redirects back to the cart after updating the quantity.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'key' => 'required|string',
            'quantity' => 'required|integer|min:0',
            'update_type' => 'nullable|string',
            'sku_id' => 'nullable|integer',
        ]);

        $jobId = $request->input('key');
        $quantity = (int) $request->input('quantity');
        $skuId = $request->input('sku_id') ? (int) $request->input('sku_id') : null;

        $this->cart->updateItemQuantity($jobId, $quantity, $skuId);

        return back()->with('success', 'Quantità aggiornata!');
    }

    /**
     * Clear all items from the cart.
     *
     * Completely empties the cart stored in the session via the CartManager.
     *
     * @return RedirectResponse Redirects back.
     */
    public function clear(): RedirectResponse
    {
        $this->cart->clear();

        return back()->with('success', 'Carrello svuotato!');
    }

    /**
     * Return a real-time price calculation for a product configuration (API Endpoint).
     *
     * Business Logic:
     * This method is polled asynchronously by the frontend (via JS/Livewire) whenever a user
     * changes an option on the product page (e.g., changes quantity, types a custom area dimension,
     * or selects a new print placement). It instantly recalculates the total price, unit price,
     * and detects if a discount is currently active, returning the data as JSON to update the UI instantly.
     *
     * @param  Request  $request  The incoming request containing product configuration parameters.
     * @return JsonResponse Returns JSON with unit_price, total_price, quantity, and discount_applied flag.
     */
    public function price(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'width' => 'nullable|numeric|min:0.1',
            'height' => 'nullable|numeric|min:0.1',
            'selected_options' => 'nullable|array',
            'quantities' => 'nullable|array',
        ]);

        $product = Product::findOrFail((int) $validated['product_id']);
        $quantity = (int) $validated['quantity'];
        $width = isset($validated['width']) ? (float) $validated['width'] : null;
        $height = isset($validated['height']) ? (float) $validated['height'] : null;

        $unitPrice = $product->getPriceForQuantity($quantity);

        $billedAreaPerUnit = 0.0;
        if ($product->product_class === ProductClass::AreaBased && $width > 0 && $height > 0) {
            $billedAreaTotal = $product->calculateTotalBilledArea($quantity, $width, $height);
            $billedAreaPerUnit = $quantity > 0 ? $billedAreaTotal / $quantity : 0.0;
            $unitPrice *= $billedAreaPerUnit;
        }

        $totalPrice = $product->calculateTotalPrice(
            $quantity,
            $validated['quantities'] ?? [],
            $width,
            $height,
            $validated['selected_options'] ?? []
        );

        $activeSku = $product->getActiveSku($validated['selected_options'] ?? []) ?? $product->skus->first();
        $baseSkuPrice = $activeSku && $activeSku->override_price !== null ? (float) $activeSku->override_price : (float) $product->price;

        $basePrice = $product->product_class === ProductClass::AreaBased && $width > 0 && $height > 0
            ? $baseSkuPrice * $billedAreaPerUnit
            : $baseSkuPrice;

        return response()->json([
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'quantity' => $quantity,
            'discount_applied' => $unitPrice < $basePrice,
        ]);
    }
}
