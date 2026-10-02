<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProductClass;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class CartItemPresenter
{
    /**
     * @param  Collection<string, array<string, mixed>>  $rawItems
     * @param  Collection<int, Product>  $products
     * @param  array{
     *     skus: Collection<int, ProductSku>,
     *     options: Collection<int, VariationOption>,
     *     types: Collection<int, VariationType>
     * }  $relatedData
     * @return array{
     *     items: array<string, array<string, mixed>>,
     *     total_savings: float,
     *     total_qty: int
     * }
     */
    public function present(Collection $rawItems, Collection $products, array $relatedData): array
    {
        $items = [];
        $totalSavings = 0.0;
        $totalQty = 0;

        foreach ($rawItems as $jobId => $item) {
            $product = $products->get((int) $item['product_id']);
            $quantity = $this->itemQuantity($item);
            $prices = $product
                ? $this->itemPrices($product, $item, $quantity)
                : ['base_price' => 0.0, 'discounted_price' => 0.0];
            $selectedOptions = $item['selected_options'] ?? [];
            $color = $this->itemColor($item, $relatedData['options'], $relatedData['types']);

            $items[$jobId] = array_merge($item, [
                'job_id' => $jobId,
                'product' => $product,
                'cat_slug' => $product?->category->slug ?? 'catalogo',
                'qty' => $quantity,
                'base_price' => $prices['base_price'],
                'disc_price' => $prices['discounted_price'],
                'is_discounted' => $prices['discounted_price'] > 0
                    && $prices['discounted_price'] < $prices['base_price'],
                'display_image' => $this->displayImage($product, $item),
                'color_name' => $color['name'],
                'color_hexes' => $color['hexes'],
                'placement_names' => $this->placementNames(
                    $selectedOptions,
                    $relatedData['options'],
                    $relatedData['types'],
                ),
                'size_rows' => $this->sizeRows($jobId, $item, $relatedData['skus']),
            ]);

            $totalQty += $quantity;
            if ($product && $prices['discounted_price'] > 0) {
                $totalSavings += max(
                    0.0,
                    ($prices['base_price'] - $prices['discounted_price']) * $quantity,
                );
            }
        }

        return [
            'items' => $items,
            'total_savings' => $totalSavings,
            'total_qty' => $totalQty,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function itemQuantity(array $item): int
    {
        return is_array($item['quantities'] ?? null)
            ? (int) array_sum($item['quantities'])
            : (int) ($item['quantity'] ?? 1);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{base_price: float, discounted_price: float}
     */
    private function itemPrices(Product $product, array $item, int $quantity): array
    {
        $totalPrice = $product->calculateTotalPrice(
            $quantity,
            $item['quantities'] ?? [],
            isset($item['width']) ? (float) $item['width'] : null,
            isset($item['height']) ? (float) $item['height'] : null,
            $item['selected_options'] ?? [],
        );
        $discountedPrice = $quantity > 0 ? $totalPrice / $quantity : 0.0;

        $activeSku = $product->getActiveSku($item['selected_options'] ?? []) ?? $product->skus->first();
        $basePrice = $activeSku && $activeSku->override_price !== null
            ? (float) $activeSku->override_price
            : (float) $product->price;

        if ($product->product_class === ProductClass::AreaBased && isset($item['width'], $item['height'])) {
            $billedAreaTotal = $product->calculateTotalBilledArea(
                $quantity,
                (float) $item['width'],
                (float) $item['height'],
            );
            $billedAreaPerUnit = $quantity > 0 ? $billedAreaTotal / $quantity : 0.0;
            $basePrice *= $billedAreaPerUnit;
        }

        $basePriceTotal = $product->applyModifiersToTotal(
            $basePrice * $quantity,
            $quantity,
            $item['selected_options'] ?? [],
        );

        return [
            'base_price' => $quantity > 0 ? $basePriceTotal / $quantity : 0.0,
            'discounted_price' => $discountedPrice,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function displayImage(?Product $product, array $item): ?string
    {
        if (! $product instanceof Product) {
            return null;
        }

        $selectedOptionIds = isset($item['selected_options']) && is_array($item['selected_options'])
            ? Arr::flatten($item['selected_options'])
            : [];

        if ($selectedOptionIds !== []) {
            /** @var Image|null $image */
            $image = $product->images->whereIn('variation_option_id', $selectedOptionIds)->first();
            if ($image?->image_url !== null) {
                return $image->image_url;
            }
        }

        return $product->getFirstMediaUrl('images', 'thumbnail') ?: null;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  Collection<int, VariationOption>  $options
     * @param  Collection<int, VariationType>  $types
     * @return array{name: mixed, hexes: array<int, mixed>}
     */
    private function itemColor(array $item, Collection $options, Collection $types): array
    {
        $name = $item['color_name'] ?? null;
        $hexes = [];
        $selectedOptions = $item['selected_options'] ?? [];

        if (is_array($selectedOptions)) {
            foreach (Arr::flatten($selectedOptions) as $optionId) {
                $option = $options->get((int) $optionId);
                if ($option && $types->get($option->variation_type_id)?->presentation_type === 'color_swatch') {
                    $name = $option->name;
                    $hexes = $option->getHexColors();
                    break;
                }
            }
        }

        return ['name' => $name, 'hexes' => $hexes];
    }

    /**
     * @param  array<string, mixed>  $selectedOptions
     * @param  Collection<int, VariationOption>  $options
     * @param  Collection<int, VariationType>  $types
     * @return array<int, string>
     */
    private function placementNames(array $selectedOptions, Collection $options, Collection $types): array
    {
        return collect($selectedOptions)
            ->flatMap(function ($optionIds, $typeId) use ($options, $types) {
                $type = $types->get((int) $typeId);
                if ($type && $type->allow_multiple) {
                    return collect((array) $optionIds)
                        ->map(fn ($optionId) => $options->get((int) $optionId)?->name)
                        ->filter();
                }

                return [];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  Collection<int, ProductSku>  $skus
     * @return array<int, array{sku_id: int|string, name: string, qty: int, job_id: string}>
     */
    private function sizeRows(string|int $jobId, array $item, Collection $skus): array
    {
        $sizeRows = [];
        foreach ($item['quantities'] ?? [] as $skuId => $quantity) {
            if ((int) $quantity <= 0) {
                continue;
            }

            $sku = $skus->get((int) $skuId);
            $sizeRows[] = [
                'sku_id' => $skuId,
                'name' => $sku?->options->isNotEmpty()
                    ? $sku->options->pluck('name')->implode(' / ')
                    : 'Unica',
                'qty' => (int) $quantity,
                'job_id' => (string) $jobId,
            ];
        }

        return $sizeRows;
    }
}
