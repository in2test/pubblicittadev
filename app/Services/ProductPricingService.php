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
     * Category quantity discounts are applied on top of any offer price or base price.
     */
    public function getPriceForQuantity(Product $product, int $quantity = 1, ?ProductSku $sku = null): float
    {
        if ($product->offer_price > 0) {
            // Apply category quantity discount on top of the offer price.
            return $this->applyQuantityDiscount($product, (float) $product->offer_price, $quantity);
        }

        // Explicit tier price takes precedence over quantity-based category discounts.
        if ($tierPrice = $this->getTierPrice($product, $quantity, $sku)) {
            return $tierPrice;
        }

        return max(0.0, $this->quantityDiscountService->calculatePrice($product, $quantity));
    }

    public function getSkuPriceForQuantity(Product $product, int $quantity = 1, ?ProductSku $sku = null): float
    {
        if ($sku?->override_price !== null) {
            return $this->applyQuantityDiscount($product, (float) $sku->override_price, $quantity);
        }

        return $this->getPriceForQuantity($product, $quantity, $sku);
    }

    /**
     * Applies the category quantity discount to the given price.
     * Used by the price calculator when a SKU has an outlet override_price.
     */
    public function applyQuantityDiscount(Product $product, float $price, int $quantity): float
    {
        $discount = $this->quantityDiscountService->getDiscountForCategoryTree($product->category_id, $quantity);

        return max(0.0, $this->quantityDiscountService->computeDiscountedPrice($price, $discount));
    }

    /**
     * Retrieves the tier price based on quantity and optional SKU.
     */
    public function getTierPrice(Product $product, int $quantity, ?ProductSku $sku = null): ?float
    {
        $skuId = $sku?->id;

        if ($skuId) {
            $tier = $this->findTier($product, $quantity, $skuId);
            if ($tier instanceof PricingTier) {
                return (float) $tier->price_per_unit;
            }

            // If no SKU-specific tier found, use base price (no fallback to product-level tiers)
            return null;
        }

        // No specific SKU requested, look for product-level tiers
        $tier = $this->findTier($product, $quantity);
        if ($tier instanceof PricingTier) {
            return (float) $tier->price_per_unit;
        }

        if ($product->relationLoaded('pricingTiers')) {
            $matchedTiers = $product->pricingTiers
                ->filter(fn (PricingTier $t) => $t->min_quantity <= $quantity &&
                    ($t->max_quantity >= $quantity || is_null($t->max_quantity)));

            if ($matchedTiers->isEmpty()) {
                // No tier matches the quantity - return null to use base price
                return null;
            }

            $minPrice = $matchedTiers->min('price_per_unit');

            return (float) $minPrice;
        }

        // No eager-loaded tiers, query fresh
        $minPrice = $product->pricingTiers()
            ->where('min_quantity', '<=', $quantity)
            ->where(function (Builder $query) use ($quantity) {
                $query->where('max_quantity', '>=', $quantity)
                    ->orWhereNull('max_quantity');
            })
            ->min('price_per_unit');

        // If no tier matches, return null to use base price
        if ($minPrice === null) {
            return null;
        }

        return (float) $minPrice;
    }

    private function findTier(Product $product, int $quantity, ?int $skuId = null): ?PricingTier
    {
        if ($product->relationLoaded('pricingTiers')) {
            $tiers = $product->pricingTiers
                ->filter(fn (PricingTier $tier) => $tier->product_sku_id === $skuId);

            $match = $tiers
                ->filter(fn (PricingTier $tier) => $tier->min_quantity <= $quantity &&
                    ($tier->max_quantity >= $quantity || $tier->max_quantity === null))
                ->sortByDesc('min_quantity')
                ->first();

            if (! $match && ($product->price <= 0 || $product->allows_custom_size)) {
                return $tiers->sortBy('min_quantity')->first();
            }

            return $match;
        }

        $query = $product->pricingTiers()->where('product_sku_id', $skuId);
        $tier = (clone $query)
            ->where('min_quantity', '<=', $quantity)
            ->where(function (Builder $query) use ($quantity) {
                $query->where('max_quantity', '>=', $quantity)
                    ->orWhereNull('max_quantity');
            })
            ->orderByDesc('min_quantity')
            ->first();

        if (! $tier && ($product->price <= 0 || $product->allows_custom_size)) {
            return $query->orderBy('min_quantity')->first();
        }

        return $tier;
    }

    /**
     * For area-based products without explicit tiers, use the base price directly.
     */
    public function getPriceForAreaBasedProduct(Product $product): float
    {
        return (float) $product->price;
    }
}
