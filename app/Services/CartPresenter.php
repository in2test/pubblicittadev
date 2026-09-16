<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProductClass;
use App\Models\Image;
use App\Models\ProductSku;
use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class CartPresenter
{
    public function __construct(
        private readonly CartManager $cart
    ) {}

    /**
     * Build the presented data for the cart view.
     *
     * @return array{
     *     items: array<string, array<string, mixed>>,
     *     total: float,
     *     count: int,
     *     totalSavings: float,
     *     totalQty: int
     * }
     */
    public function present(): array
    {
        /** @var Collection<string, array<string, mixed>> $rawItems */
        $rawItems = collect($this->cart->getItems());

        // Batch-load all related data up front
        $products = $this->cart->getProducts();

        $allSkuIds = $rawItems->pluck('quantities')
            ->filter(fn ($item) => is_array($item))
            ->flatMap(fn ($q) => array_keys($q))
            ->unique();

        $skus = ProductSku::with('options')
            ->whereIn('id', $allSkuIds)
            ->get()
            ->keyBy('id');

        $allOptionIds = $rawItems->pluck('selected_options')
            ->filter(fn ($item) => is_array($item))
            ->flatMap(fn ($o) => Arr::flatten($o))
            ->unique();

        $options = VariationOption::whereIn('id', $allOptionIds)
            ->get()
            ->keyBy('id');

        $typeIds = $options->pluck('variation_type_id')->unique();
        $types = VariationType::whereIn('id', $typeIds)
            ->get()
            ->keyBy('id');

        // Build enriched item list
        $items = [];
        $totalSavings = 0.0;
        $totalQty = 0;

        foreach ($rawItems as $jobId => $item) {
            $product = $products->get((int) $item['product_id']);
            $qty = is_array($item['quantities'] ?? null)
                ? (int) array_sum($item['quantities'])
                : (int) ($item['quantity'] ?? 1);

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
                $basePrice = $activeSku && $activeSku->override_price !== null
                    ? (float) $activeSku->override_price
                    : (float) $product->price;

                if ($product->product_class === ProductClass::AreaBased && isset($item['width'], $item['height'])) {
                    $billedAreaTotal = $product->calculateTotalBilledArea($qty, (float) $item['width'], (float) $item['height']);
                    $billedAreaPerUnit = $qty > 0 ? $billedAreaTotal / $qty : 0.0;
                    $basePrice *= $billedAreaPerUnit;
                }

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

        return [
            'items' => $items,
            'total' => $this->cart->total(),
            'count' => $this->cart->count(),
            'totalSavings' => $totalSavings,
            'totalQty' => $totalQty,
        ];
    }
}
