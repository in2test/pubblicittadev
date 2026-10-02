<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProductClass;
use App\Models\Product;
use App\Models\ProductVariationType;
use App\Models\VariationOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductStartingPriceService
{
    public function getMinimumOrderQuantity(Product $product): int
    {
        if ($product->product_class !== ProductClass::AreaBased) {
            if (array_key_exists('pricing_tiers_min_quantity', $product->getAttributes())) {
                $minTierQty = $product->pricing_tiers_min_quantity;
            } elseif ($product->relationLoaded('pricingTiers')) {
                $minTierQty = $product->pricingTiers->min('min_quantity');
            } else {
                $minTierQty = $product->pricingTiers()->min('min_quantity');
            }

            if ($minTierQty !== null) {
                return (int) $minTierQty;
            }
        }

        return 1;
    }

    public function getStartingPrice(Product $product, bool $isOutlet = false): float
    {
        return $this->getAbsoluteMinimumPrice($product, false, $isOutlet);
    }

    public function getAbsoluteMinimumPrice(Product $product, bool $skipCache = false, bool $isOutlet = false): float
    {
        if (! $skipCache && ! $isOutlet && $product->cached_starting_price !== null) {
            return (float) $product->cached_starting_price;
        }

        $minQty = $this->getMinimumOrderQuantity($product);

        if ($product->product_class === ProductClass::AreaBased) {
            return $this->areaBasedMinimumPrice($product, $minQty, $isOutlet);
        }

        if ($product->allows_custom_size) {
            $minPriceFound = $this->customFormatMinimumPrice($product, $minQty, $isOutlet);
            if ($minPriceFound !== null) {
                return $minPriceFound;
            }
        }

        $unitPrice = $isOutlet
            ? $product->skus()->where('is_outlet', true)->min('override_price') ?? $product->getPriceForQuantity($minQty)
            : $product->getPriceForQuantity($minQty);

        return $unitPrice * $minQty;
    }

    private function areaBasedMinimumPrice(Product $product, int $minimumQuantity, bool $isOutlet): float
    {
        $billedArea = $product->calculateTotalBilledArea($minimumQuantity, 1.0, 1.0);
        $unitPrice = $isOutlet
            ? $this->getMinimumValidOutletPrice($product) ?? $product->getPriceForQuantity($minimumQuantity)
            : $product->getPriceForQuantity($minimumQuantity);

        return $unitPrice * $billedArea;
    }

    private function customFormatMinimumPrice(Product $product, int $minimumQuantity, bool $isOutlet): ?float
    {
        $formatType = $product->relationLoaded('variationTypes')
            ? $product->variationTypes->firstWhere('name', 'Formato')
            : $product->variationTypes()->where('name', 'Formato')->first();

        if (! $formatType) {
            return null;
        }

        /** @var ProductVariationType|null $productVariationType */
        $productVariationType = $product->relationLoaded('productVariationTypes')
            ? $product->productVariationTypes->where('variation_type_id', $formatType->id)->first()
            : $product->productVariationTypes()->where('variation_type_id', $formatType->id)->first();

        if (! $productVariationType) {
            return null;
        }

        $minimumPrice = null;
        foreach ($this->formatOptions($productVariationType) as $format) {
            [$width, $height] = $this->formatDimensions($product, $format);
            if (! $width || ! $height) {
                continue;
            }

            $itemsPerSheet = $product->calculateItemsPerSheet($width, $height);
            if ($itemsPerSheet <= 0) {
                continue;
            }

            $quantity = (int) ceil($minimumQuantity / $itemsPerSheet) * $itemsPerSheet;
            $price = $this->formatUnitPrice($product, $format, $quantity, $isOutlet);
            $totalPrice = $price * $quantity;

            if ($minimumPrice === null || $totalPrice < $minimumPrice) {
                $minimumPrice = $totalPrice;
            }
        }

        return $minimumPrice;
    }

    /**
     * @return Collection<int, VariationOption>
     */
    private function formatOptions(ProductVariationType $productVariationType): Collection
    {
        if ($productVariationType->relationLoaded('options')) {
            return $productVariationType->options
                ->map(fn ($option) => $option->relationLoaded('option') ? $option->option : $option->option()->first())
                ->filter();
        }

        return VariationOption::whereHas('productVariationOptions', function (Builder $query) use ($productVariationType) {
            $query->where('product_variation_type_id', $productVariationType->id);
        })->get();
    }

    /**
     * @return array{0: float|null, 1: float|null}
     */
    private function formatDimensions(Product $product, VariationOption $format): array
    {
        $name = strtolower((string) $format->name);
        if (str_contains($name, 'personalizzato') || str_contains($name, 'custom')) {
            return [
                $product->min_custom_width ?? 10.0,
                $product->min_custom_height ?? 10.0,
            ];
        }

        if (! preg_match('/(\d+(?:[.,]\d+)?)\s*x\s*(\d+(?:[.,]\d+)?)/i', $name, $matches)) {
            return [null, null];
        }

        $width = (float) str_replace(',', '.', $matches[1]);
        $height = (float) str_replace(',', '.', $matches[2]);
        if (str_contains($name, 'cm')) {
            $width *= 10;
            $height *= 10;
        }

        return [$width, $height];
    }

    private function formatUnitPrice(Product $product, VariationOption $format, int $quantity, bool $isOutlet): float
    {
        if (! $isOutlet) {
            return $product->calculateFinalUnitPrice($quantity);
        }

        $skuPrice = $product->skus()
            ->where('is_outlet', true)
            ->whereHas('options', fn ($query) => $query->where('id', $format->id))
            ->min('override_price');

        return $skuPrice !== null ? (float) $skuPrice : $product->calculateFinalUnitPrice($quantity);
    }

    public function getStartingUnitPrice(Product $product, bool $skipCache = false, bool $isOutlet = false): float
    {
        if (! $skipCache && ! $isOutlet && $product->cached_starting_unit_price !== null) {
            return (float) $product->cached_starting_unit_price;
        }

        $baseFallback = $product->offer_price > 0 ? (float) $product->offer_price : (float) $product->price;

        if ($product->product_class === ProductClass::Apparel || $product->product_class === ProductClass::AreaBased) {
            $baseFallback = $this->minimumTierUnitPrice($product) ?? $baseFallback;
        }

        if ($isOutlet) {
            $minSkuPrice = (float) $product->skus()->where('is_outlet', true)->min('override_price');

            return $minSkuPrice > 0 ? $minSkuPrice : $baseFallback;
        }

        ['prices' => $skuPrices, 'has_unpriced_sku' => $hasSkuWithoutOverride] = $this->skuOverridePrices($product);

        if ($skuPrices->isEmpty()) {
            return $baseFallback;
        }

        $minSkuPrice = (float) $skuPrices->min();

        return $hasSkuWithoutOverride ? min($baseFallback, $minSkuPrice) : $minSkuPrice;
    }

    private function minimumTierUnitPrice(Product $product): ?float
    {
        if (array_key_exists('pricing_tiers_min_price_per_unit', $product->getAttributes())) {
            $minTierPrice = $product->pricing_tiers_min_price_per_unit;
        } elseif ($product->relationLoaded('pricingTiers')) {
            $minTierPrice = $product->pricingTiers->min('price_per_unit');
        } else {
            $minTierPrice = $product->pricingTiers()->min('price_per_unit');
        }

        return $minTierPrice !== null ? (float) $minTierPrice : null;
    }

    /**
     * @return array{prices: Collection<int, float>, has_unpriced_sku: bool}
     */
    private function skuOverridePrices(Product $product): array
    {
        if (array_key_exists('skus_min_override_price', $product->getAttributes())) {
            $minSkuOverride = $product->skus_min_override_price;
            $skuPrices = $minSkuOverride !== null ? collect([(float) $minSkuOverride]) : collect();
            $hasSkuWithoutOverride = $product->has_sku_without_override ?? false;
        } elseif ($product->relationLoaded('skus')) {
            $skuPrices = $product->skus
                ->filter(fn ($sku) => $sku->override_price !== null)
                ->pluck('override_price')
                ->map(fn ($price) => (float) $price);
            $hasSkuWithoutOverride = $product->skus->filter(fn ($sku) => $sku->override_price === null)->isNotEmpty();
        } else {
            $skuPrices = $product->skus()->whereNotNull('override_price')->pluck('override_price')->map(fn ($price) => (float) $price);
            $hasSkuWithoutOverride = $product->skus()->whereNull('override_price')->exists();
        }

        return [
            'prices' => $skuPrices,
            'has_unpriced_sku' => $hasSkuWithoutOverride,
        ];
    }

    /**
     * Get the minimum valid outlet price among all outlet SKUs.
     * Only considers SKUs that are marked as outlets and have a positive override_price.
     *
     * @return float|null The minimum outlet price, or null if no valid outlet SKU exists.
     */
    private function getMinimumValidOutletPrice(Product $product): ?float
    {
        $minPrice = $product->skus()
            ->where('is_outlet', true)
            ->whereNotNull('override_price')
            ->where('override_price', '>', 0)
            ->min('override_price');

        return $minPrice > 0 ? (float) $minPrice : null;
    }

    public function updateCachedPrices(Product $product): void
    {
        $product->cached_starting_price = $this->getAbsoluteMinimumPrice($product, true);
        $product->cached_starting_unit_price = $this->getStartingUnitPrice($product, true);
        $product->saveQuietly();
    }
}
