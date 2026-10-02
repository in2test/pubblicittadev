<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ProductVariationOption;
use App\Models\ProductVariationType;
use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductSkuSynchronizer
{
    /**
     * Synchronize variation types, options, SKUs, and relations for a product.
     *
     * @param  array<string, mixed>  $data
     * @param  Collection<string, VariationOption>  $colorOptionsCache
     */
    public function sync(
        Product $product,
        array $data,
        VariationType $colorType,
        VariationType $sizeType,
        Collection $colorOptionsCache
    ): void {
        if (empty($data['variations'])) {
            return;
        }

        [$productColorType, $productSizeType] = $this->productVariationTypes($product, $colorType, $sizeType);
        $sizeOptionsCache = $sizeType->options()->get()->keyBy('value');
        $skuData = $this->buildSkuData($product, $data['variations'], $sizeType, $colorOptionsCache, $sizeOptionsCache);

        $this->upsertSkus($skuData['skus']);
        $skuRecords = ProductSku::where('product_id', $product->id)->get()->keyBy('sku');

        $this->syncProductVariationOptions(
            $productColorType,
            $productSizeType,
            $skuData['used_color_option_ids'],
            $skuData['used_size_option_ids'],
        );
        $this->syncSkuOptionPivots($data['variations'], $colorOptionsCache, $sizeOptionsCache, $skuRecords);
    }

    /**
     * @return array{ProductVariationType, ProductVariationType}
     */
    private function productVariationTypes(Product $product, VariationType $colorType, VariationType $sizeType): array
    {
        $productColorType = ProductVariationType::firstOrCreate([
            'product_id' => $product->id,
            'variation_type_id' => $colorType->id,
        ], [
            'has_images' => true,
        ]);

        $productSizeType = ProductVariationType::firstOrCreate([
            'product_id' => $product->id,
            'variation_type_id' => $sizeType->id,
        ], [
            'has_images' => false,
        ]);

        return [$productColorType, $productSizeType];
    }

    /**
     * @param  array<int, array<string, mixed>>  $variations
     * @param  Collection<string, VariationOption>  $colorOptionsCache
     * @param  Collection<string, VariationOption>  $sizeOptionsCache
     * @return array{
     *     skus: array<int, array<string, mixed>>,
     *     used_color_option_ids: array<int, true>,
     *     used_size_option_ids: array<int, true>
     * }
     */
    private function buildSkuData(
        Product $product,
        array $variations,
        VariationType $sizeType,
        Collection $colorOptionsCache,
        Collection $sizeOptionsCache,
    ): array {
        $skus = [];
        $usedColorOptionIds = [];
        $usedSizeOptionIds = [];
        $totalVariations = count($variations);

        foreach ($variations as $index => $variation) {
            if (($index + 1) % 10 === 0 || $index + 1 === $totalVariations) {
                $progress = 40 + (int) ((($index + 1) / max($totalVariations, 1)) * 55);
                $product->update(['sync_progress' => $progress]);
            }

            $colorCode = (string) ($variation['itemColorCode'] ?? '');
            /** @var VariationOption|null $colorOption */
            $colorOption = $colorCode !== '' && $colorCode !== '0' ? $colorOptionsCache->get($colorCode) : null;
            if ($colorOption) {
                $usedColorOptionIds[$colorOption->id] = true;
            }

            foreach ($variation['skus'] ?? [] as $item) {
                $sizeOption = $this->sizeOption($item, $sizeType, $sizeOptionsCache);
                if ($sizeOption instanceof VariationOption) {
                    $usedSizeOptionIds[$sizeOption->id] = true;
                }

                $actualAvailability = (int) $item['availability'];
                $halvedQuantity = (int) floor($actualAvailability / 2);

                $skus[] = [
                    'sku' => $item['sku'],
                    'product_id' => $product->id,
                    'quantity' => $halvedQuantity,
                    'is_available' => ($item['active'] ?? true) && $halvedQuantity > 0,
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ];
            }
        }

        return [
            'skus' => $skus,
            'used_color_option_ids' => $usedColorOptionIds,
            'used_size_option_ids' => $usedSizeOptionIds,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  Collection<string, VariationOption>  $sizeOptionsCache
     */
    private function sizeOption(array $item, VariationType $sizeType, Collection $sizeOptionsCache): ?VariationOption
    {
        $sizeName = $item['skuSize']['webtext'] ?? null;
        $sizeCode = (string) ($item['skuSize']['size'] ?? '');
        if ($sizeCode === '') {
            return null;
        }

        /** @var VariationOption|null $sizeOption */
        $sizeOption = $sizeOptionsCache->get($sizeCode);
        if (! $sizeOption && $sizeName) {
            $sizeOption = VariationOption::create([
                'variation_type_id' => $sizeType->id,
                'name' => $sizeName,
                'value' => $sizeCode,
            ]);
            $sizeOptionsCache->put($sizeCode, $sizeOption);
        }

        return $sizeOption;
    }

    /**
     * @param  array<int, array<string, mixed>>  $skus
     */
    private function upsertSkus(array $skus): void
    {
        if ($skus === []) {
            return;
        }

        ProductSku::upsert($skus, ['sku'], ['quantity', 'is_available', 'updated_at']);
        VariationType::firstOrCreate(
            ['name' => VariationType::MATERIALE],
            ['presentation_type' => 'text'],
        );
        VariationType::firstOrCreate(
            ['name' => VariationType::MOTIVO],
            ['presentation_type' => 'text'],
        );
    }

    /**
     * @param  array<int, true>  $colorOptionIds
     * @param  array<int, true>  $sizeOptionIds
     */
    private function syncProductVariationOptions(
        ProductVariationType $productColorType,
        ProductVariationType $productSizeType,
        array $colorOptionIds,
        array $sizeOptionIds,
    ): void {
        foreach (array_keys($colorOptionIds) as $optionId) {
            ProductVariationOption::firstOrCreate([
                'product_variation_type_id' => $productColorType->id,
                'variation_option_id' => $optionId,
            ]);
        }

        foreach (array_keys($sizeOptionIds) as $optionId) {
            ProductVariationOption::firstOrCreate([
                'product_variation_type_id' => $productSizeType->id,
                'variation_option_id' => $optionId,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $variations
     * @param  Collection<string, VariationOption>  $colorOptionsCache
     * @param  Collection<string, VariationOption>  $sizeOptionsCache
     * @param  Collection<int, ProductSku>  $skuRecords
     */
    private function syncSkuOptionPivots(
        array $variations,
        Collection $colorOptionsCache,
        Collection $sizeOptionsCache,
        Collection $skuRecords,
    ): void {
        $skuOptions = [];
        foreach ($variations as $variation) {
            $colorCode = (string) ($variation['itemColorCode'] ?? '');
            /** @var VariationOption|null $colorOption */
            $colorOption = $colorOptionsCache->get($colorCode);

            foreach ($variation['skus'] as $item) {
                /** @var ProductSku|null $sku */
                $sku = $skuRecords->get($item['sku']);
                if (! $sku) {
                    continue;
                }

                if ($colorOption) {
                    $skuOptions[] = [
                        'product_sku_id' => $sku->id,
                        'variation_option_id' => $colorOption->id,
                    ];
                }

                $sizeCode = (string) ($item['skuSize']['size'] ?? '');
                /** @var VariationOption|null $sizeOption */
                $sizeOption = $sizeOptionsCache->get($sizeCode);
                if ($sizeOption) {
                    $skuOptions[] = [
                        'product_sku_id' => $sku->id,
                        'variation_option_id' => $sizeOption->id,
                    ];
                }
            }
        }

        if ($skuRecords->isEmpty()) {
            return;
        }

        DB::table('product_sku_options')->whereIn('product_sku_id', $skuRecords->pluck('id'))->delete();
        foreach (array_chunk($skuOptions, 500) as $chunk) {
            DB::table('product_sku_options')->insertOrIgnore($chunk);
        }
    }
}
