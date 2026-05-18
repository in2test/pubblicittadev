<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Cart\StoreCartRequest;
use App\Http\Requests\Cart\UpdateCartRequest;
use App\Models\PrintPlacement;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\VariationOption;
use App\Models\VariationType;
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
     * Resolves all required related data (products, images, colour options,
     * print placements, SKU sizes) in a batch to prevent N+1 queries.
     *
     * @return View The rendered cart view.
     */
    public function index(): View
    {
        $rawItems = $this->cart->getItems();

        // --- Batch-load all related data up front ---

        $productIds = array_unique(array_column(array_values($rawItems), 'product_id'));
        $products = Product::with(['images', 'category'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        // Collect every SKU id referenced across all cart items
        $allSkuIds = [];
        foreach ($rawItems as $item) {
            if (isset($item['quantities']) && is_array($item['quantities'])) {
                $allSkuIds = array_merge($allSkuIds, array_keys($item['quantities']));
            }
        }
        $skus = ProductSku::with('options')->whereIn('id', array_unique($allSkuIds))->get()->keyBy('id');

        // Collect every option id referenced in selected_options
        $allOptionIds = [];
        foreach ($rawItems as $item) {
            if (isset($item['selected_options']) && is_array($item['selected_options'])) {
                $allOptionIds = array_merge($allOptionIds, array_values($item['selected_options']));
            }
        }
        $options = VariationOption::whereIn('id', array_unique($allOptionIds))->get()->keyBy('id');
        $typeIds = $options->pluck('variation_type_id')->unique()->toArray();
        $types = VariationType::whereIn('id', $typeIds)->get()->keyBy('id');

        // Collect every print-placement id referenced in print_placements
        $allPlacementIds = [];
        foreach ($rawItems as $item) {
            foreach ($item['print_placements'] ?? [] as $pid) {
                $allPlacementIds[] = (int) (is_array($pid) ? $pid['id'] : $pid);
            }
        }
        $placements = PrintPlacement::whereIn('id', array_unique($allPlacementIds))->get()->keyBy('id');

        // --- Build enriched item list ---

        $items = [];
        foreach ($rawItems as $jobId => $item) {
            $product = $products->get((int) $item['product_id']);

            $qty = isset($item['quantities']) && is_array($item['quantities'])
                ? array_sum(array_map(intval(...), $item['quantities']))
                : (int) ($item['quantity'] ?? 1);

            $base = $product ? (float) $product->price : 0.0;
            $disc = $product ? $product->getPriceForQuantity($qty) : 0.0;

            // Resolve display image (colour-specific or fallback)
            $displayImage = null;
            if ($product) {
                $selectedOptionIds = array_values($item['selected_options'] ?? []);
                if ($selectedOptionIds !== []) {
                    $colorImage = $product->images()->whereIn('variation_option_id', $selectedOptionIds)->first();
                    $displayImage = $colorImage?->image_url;
                }
                if (! $displayImage && isset($item['color_id'])) {
                    $colorImage = $product->images()->where('variation_option_id', $item['color_id'])->first();
                    $displayImage = $colorImage?->image_url;
                }
                if (! $displayImage) {
                    $displayImage = $product->getFirstMediaUrl('images', 'thumbnail') ?: null;
                }
            }

            // Resolve colour swatch from selected options
            $colorName = null;
            $colorHexes = [];
            foreach ($item['selected_options'] ?? [] as $optionId) {
                $opt = $options->get((int) $optionId);
                $type = $opt ? $types->get($opt->variation_type_id) : null;
                if ($type && $type->presentation_type === 'color_swatch') {
                    $colorName = $opt->name;
                    $colorHexes = $opt->getHexColors();
                    break;
                }
            }
            if (! $colorName) {
                $colorName = $item['color_name'] ?? null;
            }

            // Resolve print placement names
            $placementNames = [];
            foreach ($item['print_placements'] ?? [] as $pid) {
                $id = (int) (is_array($pid) ? $pid['id'] : $pid);
                $placementNames[] = $placements->get($id)?->name ?? ('Pos. #'.$id);
            }

            // Resolve size rows
            $sizeRows = [];
            foreach ($item['quantities'] ?? [] as $skuId => $sizeQty) {
                if ((int) $sizeQty <= 0) {
                    continue;
                }
                $sku = $skus->get((int) $skuId);
                $sizeName = $sku && $sku->options->isNotEmpty()
                    ? $sku->options->map(fn ($o) => $o->name)->implode(' / ')
                    : 'Unica';

                $sizeRows[] = [
                    'sku_id' => $skuId,
                    'name' => $sizeName,
                    'qty' => (int) $sizeQty,
                    'job_id' => $jobId,
                ];
            }

            $catSlug = $product?->category?->slug ?? 'catalogo';

            $items[$jobId] = array_merge($item, [
                'job_id' => $jobId,
                'product' => $product,
                'cat_slug' => $catSlug,
                'qty' => $qty,
                'base_price' => $base,
                'disc_price' => $disc,
                'is_discounted' => $disc > 0 && $disc < $base,
                'display_image' => $displayImage,
                'color_name' => $colorName,
                'color_hexes' => $colorHexes,
                'placement_names' => $placementNames,
                'size_rows' => $sizeRows,
            ]);
        }

        // Compute sidebar totals
        $totalSavings = 0.0;
        $totalQty = 0;
        foreach ($items as $item) {
            $totalQty += $item['qty'];
            if ($item['product'] && $item['disc_price'] > 0) {
                $totalSavings += max(0.0, ($item['base_price'] - $item['disc_price']) * $item['qty']);
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
