<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProductClass;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ProductVariationType;
use App\Models\VariationType;

class ProductPriceCalculator
{
    public function __construct(
        private readonly ProductPricingService $productPricingService,
        private readonly ProductVariantResolver $variantResolver,
    ) {}

    /**
     * Calculates the total price for an entire job (cart item or product configuration).
     *
     * @param  array<int|string, int|float|string>  $skuQuantities
     * @param  array<int|string, int|string|array<int|string, int|string>|null>  $selectedOptions
     */
    public function calculateTotalPrice(
        Product $product,
        int $totalQuantity,
        array $skuQuantities = [],
        ?float $width = null,
        ?float $height = null,
        array $selectedOptions = [],
    ): float {
        if ($totalQuantity <= 0) {
            return 0.0;
        }

        $this->ensureRelationsLoaded($product);

        // Handle area-based products separately
        if ($product->product_class === ProductClass::AreaBased) {
            return $this->calculateAreaBasedPrice(
                $product,
                $totalQuantity,
                $width,
                $height,
                $selectedOptions,
            );
        }

        // Handle standard products (fixed sizes or custom formats)
        return $this->calculateStandardProductPrice(
            $product,
            $totalQuantity,
            $skuQuantities,
            $width,
            $height,
            $selectedOptions,
        );
    }

    /**
     * Ensures required relations are loaded on the product.
     */
    private function ensureRelationsLoaded(Product $product): void
    {
        if (! $product->relationLoaded('skus')) {
            $product->loadMissing('skus.options', 'variationTypes');
        }

        if (! $product->relationLoaded('variationTypes') || ! $product->relationLoaded('productVariationTypes')) {
            $product->loadMissing(['variationTypes', 'productVariationTypes']);
        }
    }

    /**
     * Calculates price for area-based products (price per square meter).
     *
     * @param  array<int|string, int|string|array<int|string, int|string>|null>  $selectedOptions
     */
    private function calculateAreaBasedPrice(
        Product $product,
        int $totalQuantity,
        ?float $width,
        ?float $height,
        array $selectedOptions = [],
    ): float {
        if (empty($width) || empty($height)) {
            return 0.0;
        }

        $billedArea = $product->calculateTotalBilledArea($totalQuantity, $width, $height);
        $pricePerSqm = $this->getPriceForAreaBasedProduct($product, $selectedOptions);

        $total = $pricePerSqm * $billedArea;

        return $this->applyModifiersAndFormat($product, $total, $totalQuantity, $selectedOptions);
    }

    /**
     * Gets the price per square meter for area-based products.
     *
     * @param  array<int|string, int|string|array<int|string, int|string>|null>  $selectedOptions
     */
    private function getPriceForAreaBasedProduct(Product $product, array $selectedOptions = []): float
    {
        $activeSku = $this->variantResolver->getActiveSku($product, $selectedOptions) ?? $product->skus->first();

        if ($activeSku && $activeSku->override_price !== null) {
            return (float) $activeSku->override_price;
        }

        return $this->productPricingService->getPriceForAreaBasedProduct($product);
    }

    /**
     * Calculates price for standard products with fixed or custom sizes.
     *
     * @param  array<int|string, int|float|string>  $skuQuantities
     * @param  array<int|string, int|string|array<int|string, int|string>|null>  $selectedOptions
     */
    private function calculateStandardProductPrice(
        Product $product,
        int $totalQuantity,
        array $skuQuantities = [],
        ?float $width = null,
        ?float $height = null,
        array $selectedOptions = [],
    ): float {
        $total = 0.0;
        $isCustomFormat = $this->detectCustomFormat($product, $width, $height, $selectedOptions);

        // Find nearest SKU for custom format
        $nearestSku = null;
        if ($isCustomFormat && $width !== null && $height !== null) {
            $nearestSku = $this->findNearestFormatSku($product, $width, $height, $selectedOptions);
        }

        // Calculate price for each specified SKU quantity
        foreach ($skuQuantities as $skuId => $rawQty) {
            $total += $this->calculatePriceForSku(
                $product,
                $totalQuantity,
                $nearestSku,
                (int) $rawQty,
                $skuId,
                $isCustomFormat,
            );
        }

        // Handle case when no SKU quantities are specified
        if ($skuQuantities === []) {
            $total += $this->calculatePriceForDefaultSku(
                $product,
                $totalQuantity,
                $nearestSku,
                $isCustomFormat,
            );
        }

        return $this->applyModifiersAndFormat($product, $total, $totalQuantity, $selectedOptions);
    }

    /**
     * Detects if the product uses custom format (option ID 999999).
     *
     * @param  array<int|string, int|string|array<int|string, int|string>|null>  $selectedOptions
     */
    private function detectCustomFormat(Product $product, ?float $width, ?float $height, array $selectedOptions = []): bool
    {
        if (! $product->allows_custom_size || ! $width || ! $height) {
            return false;
        }

        return in_array(999999, $selectedOptions);
    }

    /**
     * Finds the nearest format SKU for custom size requests.
     *
     * @param  array<int|string, int|string|array<int|string, int|string>|null>  $selectedOptions
     */
    private function findNearestFormatSku(
        Product $product,
        float $width,
        float $height,
        array $selectedOptions = [],
    ): ?ProductSku {
        $nearestFormatId = $this->variantResolver->getNearestFormatOptionId($product, $width, $height);

        if (! $nearestFormatId) {
            return null;
        }

        $targetOptions = $selectedOptions;
        $formatType = $product->variationTypes->firstWhere('name', 'Formato');

        if ($formatType && isset($targetOptions[$formatType->id])) {
            $targetOptions[$formatType->id] = $nearestFormatId;
        }

        return $this->variantResolver->getActiveSku($product, $targetOptions);
    }

    /**
     * Calculates price for a specific SKU.
     */
    private function calculatePriceForSku(
        Product $product,
        int $totalQuantity,
        ?ProductSku $nearestSku,
        int $skuQty,
        string|int $skuId,
        bool $isCustomFormat,
    ): float {
        $sku = $product->skus->firstWhere('id', $skuId);

        // Use nearest SKU if custom format and available
        if ($isCustomFormat && $nearestSku instanceof ProductSku) {
            $sku = $nearestSku;
        }

        $unitPrice = $this->productPricingService->getSkuPriceForQuantity($product, $totalQuantity, $sku);

        // Apply custom format surcharge
        if ($isCustomFormat) {
            $unitPrice *= 1.20;
        }

        return $unitPrice * $skuQty;
    }

    /**
     * Calculates price for the default SKU when no quantities are specified.
     */
    private function calculatePriceForDefaultSku(
        Product $product,
        int $totalQuantity,
        ?ProductSku $nearestSku,
        bool $isCustomFormat,
    ): float {
        $sku = null;
        if ($isCustomFormat && $nearestSku instanceof ProductSku) {
            $sku = $nearestSku;
        }

        $unitPrice = $this->calculateFinalUnitPrice($product, $totalQuantity, null, null, $sku);

        // Check for override price on default SKU; still apply category quantity discount on top.
        if ($product->relationLoaded('skus') && $product->skus->first()) {
            $defaultSku = $product->skus->first();
            if ($defaultSku->override_price !== null) {
                $unitPrice = $this->productPricingService->applyQuantityDiscount($product, (float) $defaultSku->override_price, $totalQuantity);
            }
        }

        // Apply custom format surcharge
        if ($isCustomFormat) {
            $unitPrice *= 1.20;
        }

        return $unitPrice * $totalQuantity;
    }

    /**
     * Calculates the unit price for a single quantity.
     *
     * @param  Product  $product  The product instance
     * @param  int  $quantity  Target quantity
     * @param  float|null  $width  Item width (required for area-based model)
     * @param  float|null  $height  Item height (required for area-based model)
     * @param  ProductSku|null  $sku  Specific SKU (optional)
     * @return float The calculated final unit price
     */
    public function calculateFinalUnitPrice(Product $product, int $quantity, ?float $width = null, ?float $height = null, ?ProductSku $sku = null): float
    {
        if ($product->product_class === ProductClass::AreaBased && $width !== null && $height !== null) {
            $billedArea = $product->calculateTotalBilledArea(1, $width, $height);

            return $this->productPricingService->getPriceForQuantity($product, $quantity, $sku) * $billedArea;
        }

        return $this->productPricingService->getPriceForQuantity($product, $quantity, $sku);
    }

    /**
     * Applies modifiers and formats the final total.
     *
     * @param  array<int|string, int|string|array<int|string, int|string>|null>  $selectedOptions
     */
    private function applyModifiersAndFormat(
        Product $product,
        float $total,
        int $totalQuantity,
        array $selectedOptions,
    ): float {
        $total = $this->applyModifiersToTotal($product, $total, $totalQuantity, $selectedOptions);

        // Keep prices stable for display, quotes, and downstream persistence.
        return (float) number_format($total, 2, '.', '');
    }

    /**
     * Applies the surcharge from price modifiers.
     *
     * @param  array<int|string, int|string|array<int|string, int|string>|null>  $selectedOptions
     */
    public function applyModifiersToTotal(Product $product, float $total, int $totalQuantity, array $selectedOptions): float
    {
        if ($selectedOptions === []) {
            return $total;
        }

        // Eager-load variationTypes and their options/pivots up front to avoid N+1 queries
        $product->loadMissing(['variationTypes', 'productVariationTypes']);

        $modifiers = $this->selectedModifiers($product, $selectedOptions);

        // Apply flat modifiers per unit, then multiply by quantity
        $total += $modifiers['flat'] * $totalQuantity;

        // Apply percentage modifiers as a percentage of the total
        return $total + ($total * $modifiers['percentage'] / 100);
    }

    /**
     * @param  array<int|string, int|string|array<int|string, int|string>|null>  $selectedOptions
     * @return array{flat: float, percentage: float}
     */
    private function selectedModifiers(Product $product, array $selectedOptions): array
    {
        $flatModifiers = 0.0;
        $percentageModifiers = 0.0;

        foreach ($product->variationTypes as $type) {
            /** @var ProductVariationType|null $pivot */
            $pivot = $type->pivot;
            if (! $pivot || ! $pivot->is_modifier) {
                continue;
            }

            $typeModifiers = $this->selectedModifiersForType($product, $type, $pivot, $selectedOptions);
            $flatModifiers += $typeModifiers['flat'];
            $percentageModifiers += $typeModifiers['percentage'];
        }

        return ['flat' => $flatModifiers, 'percentage' => $percentageModifiers];
    }

    /**
     * @param  array<int|string, int|string|array<int|string, int|string>|null>  $selectedOptions
     * @return array{flat: float, percentage: float}
     */
    private function selectedModifiersForType(
        Product $product,
        VariationType $type,
        ProductVariationType $pivot,
        array $selectedOptions,
    ): array {
        $selectedOptionIds = $selectedOptions[$type->id] ?? [];
        if (! is_array($selectedOptionIds)) {
            $selectedOptionIds = [$selectedOptionIds];
        }
        $selectedOptionIds = array_filter($selectedOptionIds);

        if ($selectedOptionIds === []) {
            return ['flat' => 0.0, 'percentage' => 0.0];
        }

        /** @var ProductVariationType|null $productVariationType */
        $productVariationType = $product->productVariationTypes->firstWhere('id', $pivot->id)
            ?? $product->productVariationTypes->firstWhere('variation_type_id', $type->id);

        if ($productVariationType && ! $productVariationType->relationLoaded('options')) {
            $productVariationType->load('options');
        }

        if (! $productVariationType || ! $productVariationType->relationLoaded('options')) {
            return ['flat' => 0.0, 'percentage' => 0.0];
        }

        $modifiers = ['flat' => 0.0, 'percentage' => 0.0];
        foreach ($selectedOptionIds as $selectedOptionId) {
            $option = $productVariationType->options->firstWhere('variation_option_id', $selectedOptionId)
                ?? $productVariationType->options->firstWhere('id', $selectedOptionId);

            if (! $option) {
                continue;
            }

            $option->load('option');
            $modifier = $option->getEffectivePriceModifier();
            if ($modifier === 0.0) {
                continue;
            }

            $modifierKey = $option->getEffectiveModifierType()->value === 'percentage'
                ? 'percentage'
                : 'flat';
            $modifiers[$modifierKey] += $modifier;
        }

        return $modifiers;
    }
}
