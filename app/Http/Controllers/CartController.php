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
 * and pricing logic.
 */
class CartController extends Controller
{
    public function __construct(
        private readonly CartManager $cart
    ) {}

    /**
     * Display the cart page with all current items.
     *
     * Business Logic:
     * This method retrieves all items from the session-based CartManager. Instead of querying
     * the database for each item individually (which would cause N+1 performance issues),
     * it extracts all unique product IDs, SKU IDs, option IDs, etc., from the cart items
     * and fetches them in a single batch query.
     *
     * It then iterates over the raw session items to compute live pricing, discounts,
     * and resolve display names/images. This ensures that the cart page always reflects
     * the most up-to-date pricing and product data, even if it changed after the item was added.
     */
    public function index(): View
    {
        /** @var Collection<string, array<string, mixed>> $rawItems */
        $rawItems = collect($this->cart->getItems());

        // --- Batch-load all related data up front ---
        $products = $this->cart->getProducts();

        $allSkuIds = $rawItems->pluck('quantities')->filter(fn ($item) => is_array($item))->flatMap(fn ($q) => array_keys($q))->unique();
        $skus = ProductSku::with('options')->whereIn('id', $allSkuIds)->get()->keyBy('id');

        $allOptionIds = $rawItems->pluck('selected_options')->filter(fn ($item) => is_array($item))->flatMap(fn ($o) => Arr::flatten($o))->unique();
        $options = VariationOption::whereIn('id', $allOptionIds)->get()->keyBy('id');

        $typeIds = $options->pluck('variation_type_id')->unique();
        $types = VariationType::whereIn('id', $typeIds)->get()->keyBy('id');

        // --- Build enriched item list ---
        $items = [];
        $totalSavings = 0.0;
        $totalQty = 0;

        foreach ($rawItems as $jobId => $item) {
            $product = $products->get((int) $item['product_id']);
            $qty = is_array($item['quantities'] ?? null) ? (int) array_sum($item['quantities']) : (int) ($item['quantity'] ?? 1);

            $basePrice = 0.0;
            $discPrice = 0.0;

            if ($product) {
                $totalPrice = $product->calculateTotalPrice(
                    $qty,
                    $item['quantities'] ?? [],
                    isset($item['width']) ? (float) $item['width'] : null,
                    isset($item['height']) ? (float) $item['height'] : null,
                    $item['selected_options'] ?? []
                );

                $discPrice = $qty > 0 ? $totalPrice / $qty : 0.0;

                // Determine base price (before discounts or placement fees)
                $activeSku = $product->getActiveSku($item['selected_options'] ?? []) ?? $product->skus->first();
                $basePrice = $activeSku && $activeSku->override_price !== null ? (float) $activeSku->override_price : (float) $product->price;

                if ($product->product_class === ProductClass::AreaBased && isset($item['width'], $item['height'])) {
                    $billedAreaTotal = $product->calculateTotalBilledArea($qty, (float) $item['width'], (float) $item['height']);
                    $billedAreaPerUnit = $qty > 0 ? $billedAreaTotal / $qty : 0.0;
                    $basePrice *= $billedAreaPerUnit;
                }

                // Add modifiers (personalizations) to the base price
                $basePriceTotal = $product->applyModifiersToTotal($basePrice * $qty, $qty, $item['selected_options'] ?? []);
                $basePrice = $qty > 0 ? $basePriceTotal / $qty : 0.0;
            }

            // Determine active/main image for this configuration
            $displayImage = null;
            if ($product) {
                $selectedOptionIds = [];
                if (isset($item['selected_options']) && is_array($item['selected_options'])) {
                    $selectedOptionIds = Arr::flatten($item['selected_options']);
                }

                if ($selectedOptionIds !== []) {
                    /** @var Image|null $img */
                    $img = $product->images->whereIn('variation_option_id', $selectedOptionIds)->first();
                    $displayImage = $img?->image_url;
                }

                $displayImage ??= $product->getFirstMediaUrl('images', 'thumbnail') ?: null;
            }

            $colorName = $item['color_name'] ?? null;
            $colorHexes = [];
            if (isset($item['selected_options']) && is_array($item['selected_options'])) {
                foreach (Arr::flatten($item['selected_options']) as $optionId) {
                    $opt = $options->get((int) $optionId);
                    if ($opt && $types->get($opt->variation_type_id)?->presentation_type === 'color_swatch') {
                        $colorName = $opt->name;
                        $colorHexes = $opt->getHexColors();
                        break;
                    }
                }
            }

            $sizeRows = [];
            foreach ($item['quantities'] ?? [] as $skuId => $sizeQty) {
                if ((int) $sizeQty > 0) {
                    $sku = $skus->get((int) $skuId);
                    $sizeRows[] = [
                        'sku_id' => $skuId,
                        'name' => $sku?->options->isNotEmpty() ? $sku->options->pluck('name')->implode(' / ') : 'Unica',
                        'qty' => (int) $sizeQty,
                        'job_id' => $jobId,
                    ];
                }
            }

            /** @var array<int|string, int|array<int, int>> $selectedOptions */
            $selectedOptions = $item['selected_options'] ?? [];

            $items[$jobId] = array_merge($item, [
                'job_id' => $jobId,
                'product' => $product,
                'cat_slug' => $product?->category->slug ?? 'catalogo',
                'qty' => $qty,
                'base_price' => $basePrice,
                'disc_price' => $discPrice,
                'is_discounted' => $discPrice > 0 && $discPrice < $basePrice,
                'display_image' => $displayImage,
                'color_name' => $colorName,
                'color_hexes' => $colorHexes,
                'placement_names' => collect($selectedOptions)
                    ->flatMap(function ($optionIds, $typeId) use ($options, $types) {
                        $type = $types->get((int) $typeId);
                        if ($type && $type->allow_multiple) {
                            return collect((array) $optionIds)->map(fn ($oid) => $options->get((int) $oid)?->name)->filter();
                        }

                        return [];
                    })->all(),
                'size_rows' => $sizeRows,
            ]);

            $totalQty += $qty;
            if ($product && $discPrice > 0) {
                $totalSavings += max(0.0, ($basePrice - $discPrice) * $qty);
            }
        }

        return view('cart', [
            'items' => $items,
            'total' => $this->cart->total(),
            'count' => $this->cart->count(),
            'totalSavings' => $totalSavings,
            'totalQty' => $totalQty,
        ]);
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
     * Supports both global quantity update and size-specific updates.
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
