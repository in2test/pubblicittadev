<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Image;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ProductVariationOption;
use App\Models\ProductVariationType;
use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductSynchronizer
{
    public function __construct(private readonly NwgApiClient $apiClient) {}

    /**
     * Synchronize product variations in the database with the API data.
     */
    public function syncProduct(Product $product): void
    {
        $data = $this->apiClient->getFullProductData($product->sku);

        if (! $data) {
            return;
        }

        $product->update(['sync_progress' => 10]);

        // Get or Create generic variation types for Color and Size
        $colorType = VariationType::firstOrCreate(
            ['name' => 'Colore'],
            ['presentation_type' => 'color_swatch']
        );

        $sizeType = VariationType::firstOrCreate(
            ['name' => 'Taglia'],
            ['presentation_type' => 'select']
        );

        // For NewWave products, we cache/update the product metadata as well
        if ($product->type === Product::TYPE_NEWWAVE) {
            // Update basic info
            $updateData = [
                'name' => $data['productName'] ?? $product->name,
            ];

            if (! $product->override_price) {
                $updateData['price'] = $data['retailPrice']['price'] ?? $product->price;
            }

            if (! $product->override_description) {
                $updateData['description'] = $data['productCatalogText'] ?? $product->description;
            }

            $updateData['sync_progress'] = 20;

            $colorOptionsCache = $colorType->options()->get()->keyBy('value');
            if (! empty($data['variations'])) {
                foreach ($data['variations'] as $variationData) {
                    $variationColorCode = (string) ($variationData['itemColorCode'] ?? '');
                    if ($variationColorCode !== '' && $variationColorCode !== '0' && ! $colorOptionsCache->has($variationColorCode)) {
                        $variationColorName = is_array($variationData['itemWebColor'] ?? null)
                            ? ($variationData['itemWebColor'][0] ?? '')
                            : (string) ($variationData['itemWebColor'] ?? '');

                        // Only create if no pre-seeded record exists (preserves Italian names + hex)
                        $colorOptionsCache->put($variationColorCode, VariationOption::create([
                            'variation_type_id' => $colorType->id,
                            // Fallback name used only when no pre-seeded record was found
                            'name' => $variationColorName ?: 'Color '.$variationColorCode,
                            'value' => $variationColorCode,
                        ]));
                    }
                }
            }

            // Cache remote_images for display and persist remote URLs to the image model.
            $remoteImagesArray = [];

            if (! empty($data['pictures'])) {
                foreach ($data['pictures'] as $idx => $img) {
                    $url = $img['standardUrl'] ?? '';
                    if ($url) {
                        $remoteImagesArray[] = [
                            'id' => 'top_'.$idx,
                            'url' => $url,
                            'thumb' => $img['thumbnailUrl'] ?? '',
                            'medium' => $img['largeThumbnailUrl'] ?? '',
                            'large' => $img['standardUrl'] ?? '',
                            'variation_option_id' => null,
                        ];

                        Log::info("Remote image for SKU {$product->sku}: {$url}");
                    }
                }
            }
            if (! empty($data['variations'])) {
                foreach ($data['variations'] as $v) {
                    $colorCode = (string) ($v['itemColorCode'] ?? '');
                    /** @var VariationOption|null $colorOption */
                    $colorOption = $colorOptionsCache->get($colorCode);

                    if (! empty($v['pictures'])) {
                        foreach ($v['pictures'] as $idx => $vImg) {
                            $url = $vImg['standardUrl'] ?? '';
                            if ($url) {
                                $remoteImagesArray[] = [
                                    'id' => 'var_'.($colorCode ?: 'nc').'_'.$idx,
                                    'url' => $url,
                                    'thumb' => $vImg['thumbnailUrl'] ?? '',
                                    'medium' => $vImg['largeThumbnailUrl'] ?? '',
                                    'large' => $vImg['standardUrl'] ?? '',
                                    'variation_option_id' => $colorOption?->id,
                                ];
                            }
                        }
                    }
                }
            }

            // Process all collected images in a single pass
            $remoteImageOrder = 0;
            foreach ($remoteImagesArray as $img) {
                Image::updateOrCreate([
                    'product_id' => $product->id,
                    'image_url' => $img['url'],
                    'variation_option_id' => $img['variation_option_id'],
                ], [
                    'order_by' => $remoteImageOrder++,
                    'image_description' => $product->name,
                    'thumbnail_url' => $img['thumb'] ?: null,
                    'medium_url' => $img['medium'] ?: null,
                    'large_url' => $img['large'] ?: null,
                ]);
                Log::info("Caching remote image for SKU {$product->sku}: {$img['url']}");
            }

            if ($remoteImagesArray !== []) {
                Log::info('Cached '.count($remoteImagesArray)." remote images to 'images' table for SKU {$product->sku}");
            }

            $product->update($updateData);
        }

        $product->update(['sync_progress' => 40]);

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

        $colorOptionsCache = $colorType->options()->get()->keyBy('value');
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

        // Re-load variations with nested option relation
        $product->load([
            'variationTypes',
            'skus.options.type',
        ]);
    }

    /**
     * Fast synchronization of just the quantities for all variations.
     */
    public function syncAvailability(Product $product): void
    {
        if ($product->type !== Product::TYPE_NEWWAVE || ! $product->sku) {
            return;
        }

        $data = $this->apiClient->getProductAvailability($product->sku);

        if (empty($data['variations'])) {
            return;
        }

        foreach ($data['variations'] as $variationData) {
            if (empty($variationData['skus'])) {
                continue;
            }

            foreach ($variationData['skus'] as $item) {
                $actualAvailability = (int) $item['availability'];
                $halvedQuantity = (int) floor($actualAvailability / 2);

                $sku = $item['sku'];

                ProductSku::where('product_id', $product->id)
                    ->where('sku', $sku)
                    ->update([
                        'quantity' => $halvedQuantity,
                        'is_available' => $halvedQuantity > 0,
                        'updated_at' => now(),
                    ]);
            }
        }

        $product->touch();
    }
}
