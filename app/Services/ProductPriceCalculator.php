<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProductClass;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ProductVariationOption;
use App\Models\ProductVariationType;

class ProductPriceCalculator
{
    public function __construct(
        private readonly ProductPricingService $productPricingService,
        private readonly ProductVariantResolver $variantResolver,
    ) {}

    /**
     * Calculates the total price for an entire job (cart item or product configuration).
     */
    public function calculateTotalPrice(
        Product $product,
        int $totalQuantity,
        array $skuQuantities = [],
        ?float $width = null,
        ?float $height = null,
        array $selectedOptions = [],
    ): float {
        if ($totalQuantity === 0) {
            return 0.0;
        }

        $product->loadMissing(['skus.options', 'variationTypes']);

        if ($product->product_class === ProductClass::AreaBased) {
            if (empty($width) || empty($height)) {
                return 0.0;
            }

            $billedArea = $product->calculateTotalBilledArea($totalQuantity, $width, $height);
            $pricePerSqm = $this->productPricingService->getPriceForQuantity($product, $totalQuantity);

            $activeSku = $this->variantResolver->getActiveSku($product, $selectedOptions) ?? $product->skus->first();

            if ($activeSku && $activeSku->override_price !== null) {
                $pricePerSqm = (float) $activeSku->override_price;
            }

            $total = $pricePerSqm * $billedArea;
            $total = $this->applyModifiersToTotal($product, $total, $totalQuantity, $selectedOptions);

            return (float) number_format($total, 2, '.', '');
        }

        $total = 0.0;

        $isCustomFormat = false;
        if ($product->allows_custom_size && $width && $height) {
            foreach ($selectedOptions as $optionId) {
                if ($optionId == 999999) {
                    $isCustomFormat = true;
                    break;
                }
            }
        }

        $nearestSku = null;
        if ($isCustomFormat && $width !== null && $height !== null) {
            $nearestFormatId = $this->variantResolver->getNearestFormatOptionId($product, $width, $height);
            if ($nearestFormatId) {
                $targetOptions = $selectedOptions;
                $formatType = $product->variationTypes->firstWhere('name', 'Formato');
                if ($formatType && isset($targetOptions[$formatType->id])) {
                    $targetOptions[$formatType->id] = $nearestFormatId;
                }
                $nearestSku = $this->variantResolver->getActiveSku($product, $targetOptions);
            }
        }

        foreach ($skuQuantities as $skuId => $rawQty) {
            $skuQty = (int) $rawQty;
            if ($skuQty > 0) {
                $sku = $product->skus->firstWhere('id', $skuId);

                if ($isCustomFormat && $nearestSku instanceof ProductSku) {
                    $sku = $nearestSku;
                }

                $unitPrice = $this->calculateFinalUnitPrice($product, $skuQty, null, null, $sku);

                if ($sku && $sku->override_price !== null) {
                    $unitPrice = (float) $sku->override_price;
                }

                if ($isCustomFormat) {
                    $unitPrice *= 1.20;
                }

                $total += $unitPrice * $skuQty;
            }
        }

        if ($skuQuantities === []) {
            $sku = null;
            if ($isCustomFormat && $nearestSku instanceof ProductSku) {
                $sku = $nearestSku;
            }

            $unitPrice = $this->calculateFinalUnitPrice($product, $totalQuantity, null, null, $sku);

            if ($sku instanceof ProductSku && $sku->override_price !== null) {
                $unitPrice = (float) $sku->override_price;
            }

            if ($isCustomFormat) {
                $unitPrice *= 1.20;
            }

            $total += $unitPrice * $totalQuantity;
        }

        $total = $this->applyModifiersToTotal($product, $total, $totalQuantity, $selectedOptions);

        return (float) number_format($total, 2, '.', '');
    }

    /**
     * Calculates the unit price for a single quantity.
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
     * Applies the surcharge from price modifiers.
     */
    public function applyModifiersToTotal(Product $product, float $total, int $totalQuantity, array $selectedOptions): float
    {
        if ($selectedOptions === []) {
            return $total;
        }

        $product->loadMissing('variationTypes');

        $flatModifiers = 0.0;
        $percentageModifiers = 0.0;

        foreach ($product->variationTypes as $type) {
            /** @var ProductVariationType|null $pivot */
            $pivot = $type->pivot;
            if (! $pivot || ! $pivot->is_modifier) {
                continue;
            }

            $selectedOptionIds = $selectedOptions[$type->id] ?? [];
            if (! is_array($selectedOptionIds)) {
                $selectedOptionIds = [$selectedOptionIds];
            }
            $selectedOptionIds = array_filter($selectedOptionIds);

            foreach ($selectedOptionIds as $selectedOptionId) {
                $productVariationOption = ProductVariationOption::where('product_variation_type_id', $pivot->id)
                    ->where('variation_option_id', $selectedOptionId)
                    ->with('option')
                    ->first();

                if ($productVariationOption) {
                    $modifier = $productVariationOption->getEffectivePriceModifier();
                    $modifierType = $productVariationOption->getEffectiveModifierType();

                    if ($modifier > 0) {
                        if ($modifierType->value === 'percentage') {
                            $percentageModifiers += $modifier;
                        } else {
                            $flatModifiers += $modifier;
                        }
                    }
                }
            }
        }

        $total += $flatModifiers * $totalQuantity;

        if ($percentageModifiers > 0) {
            $total += $total * ($percentageModifiers / 100.0);
        }

        return $total;
    }
}
