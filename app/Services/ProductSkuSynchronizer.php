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

        // Attach types to product
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

        $sizeOptionsCache = $sizeType->options()->get()->keyBy('value');

        $skusToUpsert = [];
        $totalVariations = count($data['variations']);
        $processedVariations = 0;

        $usedColorOptionIds = [];
        $usedSizeOptionIds = [];

        foreach ($data['variations'] as $variationData) {
            $processedVariations++;

            if ($processedVariations % 10 === 0 || $processedVariations === $totalVariations) {
                $progress = 40 + (int) (($processedVariations / max($totalVariations, 1)) * 55);
                $product->update(['sync_progress' => $progress]);
            }

            $colorCode = (string) ($variationData['itemColorCode'] ?? '');
            $colorOption = null;

            if ($colorCode !== '' && $colorCode !== '0') {
                /** @var VariationOption|null $colorOption */
                $colorOption = $colorOptionsCache->get($colorCode);
                if ($colorOption) {
                    $usedColorOptionIds[$colorOption->id] = true;
                }
            }

            if (empty($variationData['skus'])) {
                continue;
            }

            foreach ($variationData['skus'] as $item) {
                $actualAvailability = (int) $item['availability'];
                $halvedQuantity = (int) floor($actualAvailability / 2);

                $sizeName = $item['skuSize']['webtext'] ?? null;
                $sizeCode = (string) ($item['skuSize']['size'] ?? '');
                $sizeOption = null;

                if ($sizeCode !== '') {
                    /** @var VariationOption|null $sizeOption */
                    $sizeOption = $sizeOptionsCache->get($sizeCode);

                    if (! $sizeOption && $sizeName) {
                        // Only create if no pre-seeded record exists (preserves canonical size names)
                        $sizeOption = VariationOption::create([
                            'variation_type_id' => $sizeType->id,
                            'name' => $sizeName,
                            'value' => $sizeCode,
                        ]);
                        $sizeOptionsCache->put($sizeCode, $sizeOption);
                    }

                    if ($sizeOption) {
                        $usedSizeOptionIds[$sizeOption->id] = true;
                    }
                }

                $skusToUpsert[] = [
                    'sku' => $item['sku'],
                    'product_id' => $product->id,
                    'quantity' => $halvedQuantity,
                    'is_available' => ($item['active'] ?? true) && $halvedQuantity > 0,
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ];
            }
        }

        // Batch upsert SKUs
        if ($skusToUpsert !== []) {
            ProductSku::upsert(
                $skusToUpsert,
                ['sku'],
                ['quantity', 'is_available', 'updated_at']
            );
            VariationType::firstOrCreate(
                ['name' => VariationType::MATERIALE],
                ['presentation_type' => 'text']
            );
            VariationType::firstOrCreate(
                ['name' => VariationType::MOTIVO],
                ['presentation_type' => 'text']
            );
        }

        // Fetch back SKUs to map their options
        $skuRecords = ProductSku::where('product_id', $product->id)->get()->keyBy('sku');
        $skuOptionsData = [];

        foreach ($data['variations'] as $variationData) {
            $colorCode = (string) ($variationData['itemColorCode'] ?? '');
            /** @var VariationOption|null $colorOption */
            $colorOption = $colorOptionsCache->get($colorCode);

            foreach ($variationData['skus'] as $item) {
                /** @var ProductSku|null $skuObj */
                $skuObj = $skuRecords->get($item['sku']);
                if ($skuObj) {
                    if ($colorOption) {
                        $skuOptionsData[] = [
                            'product_sku_id' => $skuObj->id,
                            'variation_option_id' => $colorOption->id,
                        ];
                    }

                    $sizeCode = (string) ($item['skuSize']['size'] ?? '');
                    /** @var VariationOption|null $sizeOption */
                    $sizeOption = $sizeOptionsCache->get($sizeCode);
                    if ($sizeOption) {
                        $skuOptionsData[] = [
                            'product_sku_id' => $skuObj->id,
                            'variation_option_id' => $sizeOption->id,
                        ];
                    }
                }
            }
        }

        // Assign valid options to product variations
        foreach (array_keys($usedColorOptionIds) as $optId) {
            ProductVariationOption::firstOrCreate([
                'product_variation_type_id' => $productColorType->id,
                'variation_option_id' => $optId,
            ]);
        }
        foreach (array_keys($usedSizeOptionIds) as $optId) {
            ProductVariationOption::firstOrCreate([
                'product_variation_type_id' => $productSizeType->id,
                'variation_option_id' => $optId,
            ]);
        }

        // Delete old pivot entries and insert new ones
        if ($skuRecords->isNotEmpty()) {
            $skuIds = $skuRecords->pluck('id')->toArray();
            DB::table('product_sku_options')->whereIn('product_sku_id', $skuIds)->delete();

            // Chunk inserts if too many
            foreach (array_chunk($skuOptionsData, 500) as $chunk) {
                DB::table('product_sku_options')->insertOrIgnore($chunk);
            }
        }
    }
}
