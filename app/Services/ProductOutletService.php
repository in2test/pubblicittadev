<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSku;
use Carbon\CarbonImmutable;

class ProductOutletService
{
    /**
     * @return array<string, float>
     */
    public function getOutletPricesPerOption(Product $product): array
    {
        $outletSkusWithPrice = $product->skus()
            ->where('is_outlet', true)
            ->whereNotNull('override_price')
            ->where('override_price', '>', 0)
            ->with('options')
            ->get();

        if ($outletSkusWithPrice->isEmpty()) {
            return [];
        }

        $pricesPerOption = [];
        foreach ($outletSkusWithPrice as $sku) {
            foreach ($sku->options as $optionRelation) {
                $option = $optionRelation->option ?? null;
                if (! $option) {
                    continue;
                }

                $key = $option->type.':'.$option->id;
                if (! isset($pricesPerOption[$key]) || $sku->override_price < $pricesPerOption[$key]) {
                    $pricesPerOption[$key] = (float) $sku->override_price;
                }
            }
        }

        return $pricesPerOption;
    }

    public function getMinimumOutletPrice(Product $product): float
    {
        $minimumPrice = $product->skus()
            ->where('is_outlet', true)
            ->whereNotNull('override_price')
            ->where('override_price', '>', 0)
            ->min('override_price');

        return $minimumPrice > 0 ? (float) $minimumPrice : 0.0;
    }

    public function hasValidOutletPrice(Product $product): bool
    {
        return $product->skus()
            ->where('is_outlet', true)
            ->whereNotNull('override_price')
            ->where('override_price', '>', 0)
            ->exists();
    }

    /**
     * @param  array<int, array<string, mixed>>  $exposedVariants
     */
    public function updateSkuOutletSettings(
        Product $product,
        bool $productIsOutlet,
        ?float $productPrice,
        array $exposedVariants,
    ): void {
        $skuAttributesById = [];
        $skuIdsByOption = [];

        foreach ($product->skus()->with('options')->get() as $sku) {
            $skuAttributesById[$sku->id] = $sku->getAttributes();

            foreach ($sku->options as $option) {
                $skuIdsByOption[$option->id][] = $sku->id;
            }
        }

        if ($productIsOutlet) {
            $skuUpdates = $skuAttributesById;
            $this->markProductSkusAsOutlet($skuUpdates, $productPrice);
            $this->applyProductOutletVariantPrices($skuUpdates, $skuIdsByOption, $exposedVariants);
        } elseif ($exposedVariants !== []) {
            $skuUpdates = [];
            $this->applyVariantOutletSettings($skuUpdates, $skuAttributesById, $skuIdsByOption, $exposedVariants);
        } else {
            $skuUpdates = $skuAttributesById;
            $this->resetProductSkuOutletSettings($skuUpdates);
        }

        if ($skuUpdates === []) {
            return;
        }

        foreach ($skuUpdates as &$skuUpdate) {
            $skuUpdate['updated_at'] = CarbonImmutable::now();
        }
        unset($skuUpdate);

        ProductSku::query()->upsert(array_values($skuUpdates), ['id'], [
            'is_outlet',
            'override_price',
            'updated_at',
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $skuUpdates
     */
    private function markProductSkusAsOutlet(array &$skuUpdates, ?float $productPrice): void
    {
        foreach ($skuUpdates as &$skuUpdate) {
            $skuUpdate['is_outlet'] = true;
            if ($productPrice !== null) {
                $skuUpdate['override_price'] = $productPrice;
            }
        }
        unset($skuUpdate);
    }

    /**
     * @param  array<int, array<string, mixed>>  $skuUpdates
     * @param  array<int, array<int>>  $skuIdsByOption
     * @param  array<int, array<string, mixed>>  $exposedVariants
     */
    private function applyProductOutletVariantPrices(
        array &$skuUpdates,
        array $skuIdsByOption,
        array $exposedVariants,
    ): void {
        foreach ($exposedVariants as $variant) {
            if (! isset($variant['option_id']) || empty($variant['outlet_price'])) {
                continue;
            }

            $optionId = (int) $variant['option_id'];
            foreach ($skuIdsByOption[$optionId] ?? [] as $skuId) {
                $skuUpdates[$skuId]['is_outlet'] = (bool) ($variant['is_outlet'] ?? true);
                $skuUpdates[$skuId]['override_price'] = (float) $variant['outlet_price'];
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $skuUpdates
     * @param  array<int, array<string, mixed>>  $skuAttributesById
     * @param  array<int, array<int>>  $skuIdsByOption
     * @param  array<int, array<string, mixed>>  $exposedVariants
     */
    private function applyVariantOutletSettings(
        array &$skuUpdates,
        array $skuAttributesById,
        array $skuIdsByOption,
        array $exposedVariants,
    ): void {
        foreach ($exposedVariants as $variant) {
            $optionId = (int) ($variant['option_id'] ?? 0);
            if ($optionId <= 0) {
                continue;
            }

            $isOutlet = (bool) ($variant['is_outlet'] ?? false);
            $price = isset($variant['outlet_price']) && $variant['outlet_price'] !== ''
                ? (float) $variant['outlet_price']
                : null;

            foreach ($skuIdsByOption[$optionId] ?? [] as $skuId) {
                $skuUpdates[$skuId] ??= $skuAttributesById[$skuId];
                $skuUpdates[$skuId]['is_outlet'] = $isOutlet;
                if ($price !== null) {
                    $skuUpdates[$skuId]['override_price'] = $price;
                } elseif (! $isOutlet) {
                    $skuUpdates[$skuId]['override_price'] = null;
                }

            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $skuUpdates
     */
    private function resetProductSkuOutletSettings(array &$skuUpdates): void
    {
        foreach ($skuUpdates as &$skuUpdate) {
            $skuUpdate['is_outlet'] = false;
            $skuUpdate['override_price'] = null;
        }
        unset($skuUpdate);
    }
}
