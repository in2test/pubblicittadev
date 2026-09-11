<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PricingTier;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Database\Eloquent\Builder;

class ProductPricingService
{
    public function __construct(
        private readonly QuantityDiscountService $quantityDiscountService,
    ) {}

    /**
     * Calculates the price for a given quantity, optionally including a specific SKU.
     * If an offer price is active, the offer price is returned regardless of quantity.
     */
    public function getPriceForQuantity(Product $product, int $quantity = 1, ?ProductSku $sku = null): float
    {
        if ($product->offer_price > 0) {
            return (float) $product->offer_price;
        }

        // An explicit offer price takes precedence over every quantity-based rule.
        if ($tierPrice = $this->getTierPrice($product, $quantity, $sku)) {
            return $tierPrice;
        }

        return max(0.0, $this->quantityDiscountService->calculatePrice($product, $quantity));
    }

    /**
     * Retrieves the tier price based on quantity and optional SKU.
     */
    public function getTierPrice(Product $product, int $quantity, ?ProductSku $sku = null): ?float
    {
        $findTier = function (?int $skuId) use ($product, $quantity): ?PricingTier {
            if ($product->relationLoaded('pricingTiers')) {
                $tiers = $product->pricingTiers
                    ->filter(fn (PricingTier $t) => $t->product_sku_id === $skuId);

                $match = $tiers
                    ->filter(fn (PricingTier $t) => $t->min_quantity <= $quantity &&
                        ($t->max_quantity >= $quantity || is_null($t->max_quantity)))
                    ->sortByDesc('min_quantity')
                    ->first();

                if (! $match && ($product->price <= 0 || $product->allows_custom_size)) {
                    // Custom or on-request products may use their first tier as a starting price.
                    return $tiers->sortBy('min_quantity')->first();
                }

                return $match;
            }

            $query = $product->pricingTiers()->where('product_sku_id', $skuId);

            /** @var PricingTier|null $tier */
            $tier = (clone $query)
                ->where('min_quantity', '<=', $quantity)
                ->where(function (Builder $query) use ($quantity) {
                    $query->where('max_quantity', '>=', $quantity)
                        ->orWhereNull('max_quantity');
                })
                ->orderByDesc('min_quantity')
                ->first();

            if (! $tier && ($product->price <= 0 || $product->allows_custom_size)) {
                /** @var PricingTier|null $fallbackTier */
                $fallbackTier = $query->orderBy('min_quantity')->first();

                // Mirror the eager-loaded fallback when no tier covers the requested quantity.
                return $fallbackTier;
            }

            return $tier;
        };

        $skuId = $sku?->id;

        if ($skuId) {
            $tier = $findTier($skuId);
            if ($tier instanceof PricingTier) {
                return (float) $tier->price_per_unit;
            }
        }

        $tier = $findTier(null);
        if ($tier instanceof PricingTier) {
            return (float) $tier->price_per_unit;
        }

        if ($product->relationLoaded('pricingTiers')) {
            $matchedTiers = $product->pricingTiers
                ->filter(fn (PricingTier $t) => $t->min_quantity <= $quantity &&
                    ($t->max_quantity >= $quantity || is_null($t->max_quantity)));

            if ($matchedTiers->isEmpty()) {
                if ($product->price <= 0 || $product->allows_custom_size) {
                    $minPrice = $product->pricingTiers->sortBy('min_quantity')->first()?->price_per_unit;
                } else {
                    $minPrice = null;
                }
            } else {
                $minPrice = $matchedTiers->min('price_per_unit');
            }

            return $minPrice !== null ? (float) $minPrice : null;
        }

        $minPrice = $product->pricingTiers()
            ->where('min_quantity', '<=', $quantity)
            ->where(function (Builder $query) use ($quantity) {
                $query->where('max_quantity', '>=', $quantity)
                    ->orWhereNull('max_quantity');
            })
            ->min('price_per_unit');

        if ($minPrice === null && ($product->price <= 0 || $product->allows_custom_size)) {
            $minPrice = $product->pricingTiers()->orderBy('min_quantity')->value('price_per_unit');
            // Preserve the starting-price behavior for custom and on-request products.
        }

        return $minPrice !== null ? (float) $minPrice : null;
    }
}
