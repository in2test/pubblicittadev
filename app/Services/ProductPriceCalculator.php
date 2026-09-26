<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProductClass;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ProductVariationOption;
use App\Models\ProductVariationType;
use Illuminate\Support\Facades\Log;

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

        // Product is already eager-loaded with required relations from getProducts() or other contexts
        // Only load missing relations if called directly without eager loading
        if (! $product->relationLoaded('skus')) {
            $product->loadMissing('skus.options', 'variationTypes');
        }

        if ($product->product_class === ProductClass::AreaBased) {
            if (empty($width) || empty($height)) {
                return 0.0;
            }

            $billedArea = $product->calculateTotalBilledArea($totalQuantity, $width, $height);
            // For area-based products, use the base price directly without tier logic
            $pricePerSqm = $this->productPricingService->getPriceForAreaBasedProduct($product);

            $activeSku = $this->variantResolver->getActiveSku($product, $selectedOptions) ?? $product->skus->first();

            if ($activeSku && $activeSku->override_price !== null) {
                $pricePerSqm = (float) $activeSku->override_price;
            }

            $total = $pricePerSqm * $billedArea;
            $total = $this->applyModifiersToTotal($product, $total, $totalQuantity, $selectedOptions);

            // Keep prices stable for display, quotes, and downstream persistence.
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

                // Use $totalQuantity for the discount-tier lookup so that volume thresholds
                // are evaluated against the grand total across all sizes, not the per-SKU qty.
                $unitPrice = $this->calculateFinalUnitPrice($product, $totalQuantity, null, null, $sku);

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

            // Check for override price on the default SKU before calculating unit price
            if ($product->relationLoaded('skus') && $product->skus->first()) {
                $defaultSku = $product->skus->first();
                if ($defaultSku->override_price !== null) {
                    $unitPrice = (float) $defaultSku->override_price;
                } else {
                    $unitPrice = $this->calculateFinalUnitPrice($product, $totalQuantity, null, null, $sku);
                }
            } else {
                $unitPrice = $this->calculateFinalUnitPrice($product, $totalQuantity, null, null, $sku);
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

        // TODO(human): Fix flat modifier calculation - currently returns 800 instead of 980
        // The issue is in how the pivot data is accessed from eager-loaded relations.
        // Need to verify that $pivot->is_modifier works correctly when loaded via relation.
        // Consider using explicit query or accessing pivot through productVariationType directly.

        $flatModifiers = 0.0;
        $percentageModifiers = 0.0;

        // Debug: log the selectedOptions format
        \Log::info('applyModifiersToTotal - selectedOptions:', ['selectedOptions' => $selectedOptions]);
        \Log::info('applyModifiersToTotal - variationTypes:', ['variationTypes' => $product->variationTypes->pluck('id')->toArray()]);

        // variationTypes and productVariationTypes are already eager-loaded from getProducts()
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

            // Debug: log what we're looking for
            \Log::info("applyModifiersToTotal - type {$type->id}: pivot={$pivot?->id}, selectedOptionIds=".json_encode($selectedOptionIds));

            // Check if options are already loaded on the pivot (from eager load in getProducts)
            // If loaded, filter from the in-memory collection to avoid DB queries
            if ($product->relationLoaded('productVariationTypes')) {
                $pvt = $product->productVariationTypes->firstWhere('id', $pivot->id)
                    ?? $product->productVariationTypes->firstWhere('variation_type_id', $type->id);

                // Debug: log if pvt is found
                \Log::info("applyModifiersToTotal - pvt for type {$type->id}: ".($pvt ? 'found' : 'not found'));

                // If options are already loaded, use the in-memory collection directly
                if ($pvt && $pvt->relationLoaded('options')) {
                    foreach ($selectedOptionIds as $selectedOptionId) {
                        // Debug: log what we're looking for
                        $found = $pvt->options->firstWhere('variation_option_id', $selectedOptionId);
                        if (! $found) {
                            $found = $pvt->options->firstWhere('id', $selectedOptionId);
                        }

                        // Debug: log what we found
                        if ($found) {
                            \Log::info("applyModifiersToTotal - found option: id={$found->id}, price_modifier={$found->price_modifier}, modifier_type=".json_encode($found->modifier_type));
                        }

                        if ($found) {
                            $modifier = $found->getEffectivePriceModifier();
                            $modifierType = $found->getEffectiveModifierType();

                            // Debug: log the modifier details
                            \Log::info("applyModifiersToTotal - option id={$found->id}: modifier={$modifier}, type=" . ($modifierType instanceof \App\Enums\ModifierType ? $modifierType->value : json_encode($modifierType)));

                            // Apply modifier if it's non-zero (positive = surcharge, negative = discount)
                            if ($modifier !== 0) {
                                // Check both enum value and string for compatibility
                                if ($modifierType instanceof \App\Enums\ModifierType && $modifierType->value === 'percentage') {
                                    \Log::info("applyModifiersToTotal - applying percentage modifier: {$modifier}");
                                    $percentageModifiers += $modifier;
                                } elseif ($modifierType instanceof \App\Enums\ModifierType && $modifierType->value === 'flat') {
                                    \Log::info("applyModifiersToTotal - applying flat modifier: {$modifier}");
                                    $flatModifiers += $modifier;
                                } else {
                                    \Log::info("applyModifiersToTotal - applying flat modifier (fallback): {$modifier}");
                                    $flatModifiers += $modifier;
                                }
                            } else {
                                \Log::info("applyModifiersToTotal - skipping modifier because it's zero: {$modifier}");
                            }
                        }
                    }

                    continue; // All option lookups for this modifier type are done from memory
                }
            }

            // Fallback: query the database only if options were not already loaded
            foreach ($selectedOptionIds as $selectedOptionId) {
                $productVariationOption = ProductVariationOption::where('product_variation_type_id', $pivot->id)
                    ->where('variation_option_id', $selectedOptionId)
                    ->with('option')
                    ->first();

                if ($productVariationOption) {
                    $modifier = $productVariationOption->getEffectivePriceModifier();
                    $modifierType = $productVariationOption->getEffectiveModifierType();

                    // Apply modifier if it's non-zero (positive = surcharge, negative = discount)
                    if ($modifier !== 0) {
                        if ($modifierType->value === 'percentage') {
                            $percentageModifiers += $modifier;
                        } else {
                            $flatModifiers += $modifier;
                        }
                    }
                }
            }
        }

        // Debug: log the modifiers before applying
        \Log::info('applyModifiersToTotal - modifiers:', ['flatModifiers' => $flatModifiers, 'percentageModifiers' => $percentageModifiers]);

        // Apply flat modifiers first (per unit, then multiplied by quantity)
        $total += $flatModifiers * $totalQuantity;

        // Apply percentage modifiers to the original total (before flat modifiers)
        return $total * (1 + $percentageModifiers / 100.0);
    }
}
