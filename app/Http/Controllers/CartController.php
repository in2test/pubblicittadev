<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Cart\StoreCartRequest;
use App\Http\Requests\Cart\UpdateCartRequest;
use App\Models\Image;
use App\Models\PrintPlacement;
use App\Models\PrintSide;
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
    public function __construct(
        private readonly CartManager $cart
    ) {}

    /**
     * Display the cart page with all current items.
     *
     * Resolves all required related data (products, images, colour options,
     * print placements, SKU sizes) in a batch to prevent N+1 queries.
     */
    public function index(): View
    {
        $rawItems = collect($this->cart->getItems());

        // --- Batch-load all related data up front ---
        $productIds = $rawItems->pluck('product_id')->filter()->unique();
        $products = Product::with(['images', 'category'])->whereIn('id', $productIds)->get()->keyBy('id');

        $allSkuIds = $rawItems->pluck('quantities')->filter(fn ($item) => is_array($item))->flatMap(fn ($q) => array_keys($q))->unique();
        $skus = ProductSku::with('options')->whereIn('id', $allSkuIds)->get()->keyBy('id');

        $allOptionIds = $rawItems->pluck('selected_options')->filter(fn ($item) => is_array($item))->flatMap(fn ($o) => array_values($o))->unique();
        $options = VariationOption::whereIn('id', $allOptionIds)->get()->keyBy('id');

        $typeIds = $options->pluck('variation_type_id')->unique();
        $types = VariationType::whereIn('id', $typeIds)->get()->keyBy('id');

        $allPlacementIds = $rawItems->pluck('print_placements')->filter(fn ($item) => is_array($item))->flatten(1)->map(fn ($pid) => (int) (is_array($pid) ? $pid['id'] : $pid))->unique();
        $placements = PrintPlacement::whereIn('id', $allPlacementIds)->get()->keyBy('id');

        $allPrintSideIds = $rawItems->pluck('print_side_id')->filter()->unique();
        $printSides = PrintSide::whereIn('id', $allPrintSideIds)->get()->keyBy('id');

        // --- Build enriched item list ---
        $items = [];
        $totalSavings = 0.0;
        $totalQty = 0;

        foreach ($rawItems as $jobId => $item) {
            $product = $products->get((int) $item['product_id']);
            $qty = is_array($item['quantities'] ?? null) ? (int) array_sum($item['quantities']) : (int) ($item['quantity'] ?? 1);
            $printSideId = isset($item['print_side_id']) ? (int) $item['print_side_id'] : null;

            $basePrice = 0.0;
            $discPrice = 0.0;

            if ($product) {
                $basePrice = (float) $product->price;
                $discPrice = $product->getPriceForQuantity($qty, $printSideId);

                if ($product->pricing_model === 'area' && isset($item['width'], $item['height'])) {
                    $area = ((float) $item['width'] * (float) $item['height']) / 10000.0;
                    $billedArea = $product->min_area ? max($area, (float) $product->min_area) : $area;

                    $basePrice *= $billedArea;
                    $discPrice *= $billedArea;
                }
            }

            $displayImage = null;
            if ($product) {
                $selectedOptionIds = array_values($item['selected_options'] ?? []);

                if ($selectedOptionIds !== []) {
                    /** @var Image|null $img */
                    $img = $product->images->whereIn('variation_option_id', $selectedOptionIds)->first();
                    $displayImage = $img?->image_url;
                }

                if (! $displayImage && isset($item['color_id'])) {
                    /** @var Image|null $img */
                    $img = $product->images->firstWhere('variation_option_id', $item['color_id']);
                    $displayImage = $img?->image_url;
                }

                $displayImage ??= $product->getFirstMediaUrl('images', 'thumbnail') ?: null;
            }

            $colorName = $item['color_name'] ?? null;
            $colorHexes = [];
            foreach ($item['selected_options'] ?? [] as $optionId) {
                $opt = $options->get((int) $optionId);
                if ($opt && $types->get($opt->variation_type_id)?->presentation_type === 'color_swatch') {
                    $colorName = $opt->name;
                    $colorHexes = $opt->getHexColors();
                    break;
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
                'print_side_name' => $printSides->get($printSideId)?->name,
                'placement_names' => collect($item['print_placements'] ?? [])
                    ->map(function ($pid) use ($placements) {
                        $id = (int) (is_array($pid) ? $pid['id'] : $pid);

                        return $placements->get($id)->name ?? ('Pos. #'.$id);
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
     * Add a product to the cart.
     */
    public function add(StoreCartRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $printPlacements = json_decode((string) ($validated['print_placements'] ?? '[]'), true) ?? [];

        $product = Product::findOrFail((int) $validated['product_id']);
        $quantity = (int) $validated['quantity'];
        $printSideId = isset($validated['print_side_id']) ? (int) $validated['print_side_id'] : null;

        $width = isset($validated['width']) ? (float) $validated['width'] : null;
        $height = isset($validated['height']) ? (float) $validated['height'] : null;

        $this->cart->add(array_merge($validated, [
            'print_placements' => $printPlacements,
            'price' => $product->calculateFinalUnitPrice($quantity, $printPlacements, $printSideId, $width, $height),
            'quantity' => $quantity,
        ]));

        return redirect()->route('cart')->with('success', 'Prodotto aggiunto al carrello!');
    }

    /**
     * Update the quantity of a specific item in the cart.
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
     */
    public function remove(Request $request): RedirectResponse
    {
        $request->validate(['key' => 'required|string']);

        $this->cart->remove($request->input('key'));

        return back()->with('success', 'Prodotto rimosso dal carrello!');
    }

    /**
     * Remove multiple items from the cart.
     */
    public function removeMultiple(Request $request): RedirectResponse
    {
        $this->cart->removeMultiple($request->input('keys', []));

        return back()->with('success', 'Prodotti rimossi dal carrello!');
    }

    /**
     * Completely empty the shopping cart.
     */
    public function clear(): RedirectResponse
    {
        $this->cart->clear();

        return back()->with('success', 'Carrello svuotato!');
    }

    /**
     * Return a real-time price calculation for a product configuration.
     */
    public function price(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'print_placements' => 'nullable|string',
            'width' => 'nullable|numeric|min:0.1',
            'height' => 'nullable|numeric|min:0.1',
            'print_side_id' => 'nullable|integer|exists:print_sides,id',
        ]);

        $product = Product::findOrFail((int) $validated['product_id']);
        $quantity = (int) $validated['quantity'];
        $placements = json_decode((string) ($validated['print_placements'] ?? '[]'), true) ?? [];
        $width = isset($validated['width']) ? (float) $validated['width'] : null;
        $height = isset($validated['height']) ? (float) $validated['height'] : null;
        $printSideId = isset($validated['print_side_id']) ? (int) $validated['print_side_id'] : null;

        $unitPrice = $product->getPriceForQuantity($quantity, $printSideId);
        $billedArea = 1.0;

        if ($product->pricing_model === 'area' && $width > 0 && $height > 0) {
            $area = ($width * $height) / 10000.0;
            $billedArea = $product->min_area ? max($area, (float) $product->min_area) : $area;
            $unitPrice *= $billedArea;
        }

        $finalUnitPrice = $product->calculateFinalUnitPrice($quantity, $placements, $printSideId, $width, $height);

        $basePrice = $product->pricing_model === 'area' && $width > 0 && $height > 0
            ? (float) $product->price * $billedArea
            : (float) $product->price;

        return response()->json([
            'unit_price' => $unitPrice,
            'total_price' => $finalUnitPrice * $quantity,
            'quantity' => $quantity,
            'discount_applied' => $unitPrice < $basePrice,
        ]);
    }
}
