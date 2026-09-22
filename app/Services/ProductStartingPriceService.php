<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProductClass;
use App\Models\Product;
use App\Models\ProductVariationType;
use App\Models\VariationOption;
use Illuminate\Database\Eloquent\Builder;

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
            $billedArea = $product->calculateTotalBilledArea($minQty, 1.0, 1.0);

            $price = $isOutlet
                ? $this->getMinimumValidOutletPrice($product) ?? $product->getPriceForQuantity($minQty)
                : $product->getPriceForQuantity($minQty);

            return $price * $billedArea;
        }

        if ($product->allows_custom_size) {
            $formatType = $product->relationLoaded('variationTypes')
                ? $product->variationTypes->firstWhere('name', 'Formato')
                : $product->variationTypes()->where('name', 'Formato')->first();

            $minPriceFound = null;

            if ($formatType) {
                /** @var ProductVariationType|null $pvt */
                $pvt = $product->relationLoaded('productVariationTypes')
                    ? $product->productVariationTypes->where('variation_type_id', $formatType->id)->first()
                    : $product->productVariationTypes()->where('variation_type_id', $formatType->id)->first();

                if ($pvt) {
                    if ($pvt->relationLoaded('options')) {
                        $options = $pvt->options
                            ->map(fn ($o) => $o->relationLoaded('option') ? $o->option : $o->option()->first())
                            ->filter();
                    } else {
                        $options = VariationOption::whereHas('productVariationOptions', function (Builder $query) use ($pvt) {
                            $query->where('product_variation_type_id', $pvt->id);
                        })->get();
                    }

                    foreach ($options as $format) {
                        $width = null;
                        $height = null;
                        $name = strtolower((string) $format->name);

                        if (str_contains($name, 'personalizzato') || str_contains($name, 'custom')) {
                            $width = $product->min_custom_width ?? 10.0;
                            $height = $product->min_custom_height ?? 10.0;
                        } elseif (preg_match('/(\d+(?:[.,]\d+)?)\s*x\s*(\d+(?:[.,]\d+)?)/i', $name, $matches)) {
                            $width = (float) str_replace(',', '.', $matches[1]);
                            $height = (float) str_replace(',', '.', $matches[2]);
                            if (str_contains(strtolower($name), 'cm')) {
                                $width *= 10;
                                $height *= 10;
                            }
                        }

                        if ($width && $height) {
                            $itemsPerSheet = $product->calculateItemsPerSheet($width, $height);
                            if ($itemsPerSheet > 0) {
                                $quantity = (int) ceil($minQty / $itemsPerSheet) * $itemsPerSheet;
                                if ($quantity < $itemsPerSheet) {
                                    $quantity = $itemsPerSheet;
                                }

                                // For outlet, we try to find the minimum price among outlet SKUs that match this format
                                if ($isOutlet) {
                                    $price = $product->skus()
                                        ->where('is_outlet', true)
                                        ->whereHas('options', fn ($q) => $q->where('id', $format->id))
                                        ->min('override_price') ?? $product->calculateFinalUnitPrice($quantity);
                                } else {
                                    $price = $product->calculateFinalUnitPrice($quantity);
                                }

                                $totalPrice = $price * $quantity;
                                if ($minPriceFound === null || $totalPrice < $minPriceFound) {
                                    $minPriceFound = $totalPrice;
                                }
                            }
                        }
                    }
                }
            }

            if ($minPriceFound !== null) {
                return $minPriceFound;
            }
        }

        $unitPrice = $isOutlet
            ? $product->skus()->where('is_outlet', true)->min('override_price') ?? $product->getPriceForQuantity($minQty)
            : $product->getPriceForQuantity($minQty);

        return $unitPrice * $minQty;
    }

    public function getStartingUnitPrice(Product $product, bool $skipCache = false, bool $isOutlet = false): float
    {
        if (! $skipCache && ! $isOutlet && $product->cached_starting_unit_price !== null) {
            return (float) $product->cached_starting_unit_price;
        }

        $baseFallback = $product->offer_price > 0 ? (float) $product->offer_price : (float) $product->price;

        if ($product->product_class === ProductClass::Apparel || $product->product_class === ProductClass::AreaBased) {
            if (array_key_exists('pricing_tiers_min_price_per_unit', $product->getAttributes())) {
                $minTierPrice = $product->pricing_tiers_min_price_per_unit;
            } elseif ($product->relationLoaded('pricingTiers')) {
                $minTierPrice = $product->pricingTiers->min('price_per_unit');
            } else {
                $minTierPrice = $product->pricingTiers()->min('price_per_unit');
            }

            if ($minTierPrice !== null) {
                $baseFallback = (float) $minTierPrice;
            }
        }

        if ($isOutlet) {
            $minSkuPrice = (float) $product->skus()->where('is_outlet', true)->min('override_price');
            if ($minSkuPrice > 0) {
                return $minSkuPrice;
            }

            return $baseFallback;
        }

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

        if ($skuPrices->isNotEmpty()) {
            $minSkuPrice = (float) $skuPrices->min();
            if ($hasSkuWithoutOverride) {
                return min($baseFallback, $minSkuPrice);
            }

            return $minSkuPrice;
        }

        return $baseFallback;
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
