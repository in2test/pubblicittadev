<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariationOption;
use App\Models\ProductVariationType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class ProductVariationTypeSynchronizer
{
    /**
     * @param  array<int, array<string, mixed>>  $variationTypes
     */
    public function sync(Product $product, array $variationTypes): void
    {
        $variationTypeData = $this->buildVariationTypeData($variationTypes);
        if ($variationTypeData === []) {
            return;
        }

        $timestamp = CarbonImmutable::now();
        $productVariationTypes = $this->ensureProductVariationTypes($product, $variationTypeData, $timestamp);
        $this->syncProductVariationOptions($variationTypeData, $productVariationTypes, $timestamp);
    }

    /**
     * @param  array<int, array<string, mixed>>  $variationTypes
     * @return array<int, array{sort_order: int, variation_option_ids: array<int, int|string>}>
     */
    private function buildVariationTypeData(array $variationTypes): array
    {
        $variationTypeData = [];
        foreach ($variationTypes as $index => $variationData) {
            $variationTypeId = $variationData['variation_type_id'] ?? null;
            if (! $variationTypeId) {
                continue;
            }

            $variationTypeId = (int) $variationTypeId;
            $variationTypeData[$variationTypeId] ??= [
                'sort_order' => $index,
                'variation_option_ids' => [],
            ];
            $variationTypeData[$variationTypeId]['variation_option_ids'] = array_merge(
                $variationTypeData[$variationTypeId]['variation_option_ids'],
                $variationData['variation_option_ids'] ?? [],
            );
        }

        return $variationTypeData;
    }

    /**
     * @param  array<int, array{sort_order: int, variation_option_ids: array<int, int|string>}>  $variationTypeData
     * @return Collection<int, ProductVariationType>
     */
    private function ensureProductVariationTypes(
        Product $product,
        array $variationTypeData,
        CarbonImmutable $timestamp,
    ): Collection {
        $variationTypeIds = array_keys($variationTypeData);
        $existingVariationTypes = ProductVariationType::query()
            ->where('product_id', $product->id)
            ->whereIn('variation_type_id', $variationTypeIds)
            ->get()
            ->keyBy('variation_type_id');

        $newVariationTypes = [];
        foreach ($variationTypeData as $variationTypeId => $typeData) {
            if ($existingVariationTypes->has($variationTypeId)) {
                continue;
            }

            $newVariationTypes[] = [
                'product_id' => $product->id,
                'variation_type_id' => $variationTypeId,
                'sort_order' => $typeData['sort_order'],
                'has_images' => false,
                'is_modifier' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        if ($newVariationTypes !== []) {
            ProductVariationType::query()->insertOrIgnore($newVariationTypes);
        }

        return ProductVariationType::query()
            ->where('product_id', $product->id)
            ->whereIn('variation_type_id', $variationTypeIds)
            ->get()
            ->keyBy('variation_type_id');
    }

    /**
     * @param  array<int, array{sort_order: int, variation_option_ids: array<int, int|string>}>  $variationTypeData
     * @param  Collection<int, ProductVariationType>  $productVariationTypes
     */
    private function syncProductVariationOptions(
        array $variationTypeData,
        Collection $productVariationTypes,
        CarbonImmutable $timestamp,
    ): void {
        $optionIdsByVariationType = [];
        foreach ($variationTypeData as $variationTypeId => $typeData) {
            $productVariationType = $productVariationTypes->get($variationTypeId);
            if (! $productVariationType) {
                continue;
            }

            foreach (array_unique($typeData['variation_option_ids']) as $optionId) {
                $optionIdsByVariationType[$productVariationType->id][(int) $optionId] = true;
            }
        }

        if ($optionIdsByVariationType === []) {
            return;
        }

        $variationTypeIds = array_keys($optionIdsByVariationType);
        $existingOptionPairs = ProductVariationOption::query()
            ->whereIn('product_variation_type_id', $variationTypeIds)
            ->get()
            ->mapWithKeys(fn (ProductVariationOption $option): array => [
                $option->product_variation_type_id.'-'.$option->variation_option_id => true,
            ]);

        $newOptionRows = [];
        foreach ($optionIdsByVariationType as $productVariationTypeId => $optionIds) {
            foreach (array_keys($optionIds) as $optionId) {
                $pairKey = $productVariationTypeId.'-'.$optionId;
                if ($existingOptionPairs->has($pairKey)) {
                    continue;
                }

                $newOptionRows[] = [
                    'product_variation_type_id' => $productVariationTypeId,
                    'variation_option_id' => $optionId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        if ($newOptionRows !== []) {
            ProductVariationOption::query()->insertOrIgnore($newOptionRows);
        }
    }
}
