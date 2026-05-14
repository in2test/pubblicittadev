<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Color;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Size;
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

            $colorsCache = Color::all()->keyBy('color_code');
            if (! empty($data['variations'])) {
                foreach ($data['variations'] as $variationData) {
                    $variationColorCode = (string) ($variationData['itemColorCode'] ?? '');
                    if ($variationColorCode !== '' && $variationColorCode !== '0' && ! $colorsCache->has($variationColorCode)) {
                        $variationColorName = is_array($variationData['itemWebColor'] ?? null)
                            ? ($variationData['itemWebColor'][0] ?? '')
                            : (string) ($variationData['itemWebColor'] ?? '');

                        $colorsCache->put($variationColorCode, Color::create([
                            'color_code' => $variationColorCode,
                            'color_name' => $variationColorName ?: 'Color '.$variationColorCode,
                            'color_hex' => '#CCCCCC',
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
                            'color_ids' => [],
                            'color_id' => null,
                        ];

                        Log::info("Remote image for SKU {$product->sku}: {$url}");
                    }
                }
            }
            if (! empty($data['variations'])) {
                foreach ($data['variations'] as $v) {
                    $colorCode = (string) ($v['itemColorCode'] ?? '');
                    $color = $colorsCache->get($colorCode);
                    $colorIds = $color ? [$color->id] : [];

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
                                    'color_ids' => $colorIds,
                                    'color_id' => $color?->id,
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
                    'color_id' => $img['color_id'] ?? null,
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

        // 2. Pre-load Colors and Sizes into local cache for 1-to-1 mapping
        $colorsCache = Color::all()->keyBy('color_code');
        $sizesCache = Size::all()->keyBy('size_code');

        $variationsToUpsert = [];
        $totalVariations = count($data['variations']);
        $processedVariations = 0;

        foreach ($data['variations'] as $variationData) {
            $processedVariations++;

            // Only update progress every 10 variations to reduce DB writes
            if ($processedVariations % 10 === 0 || $processedVariations === $totalVariations) {
                $progress = 40 + (int) (($processedVariations / max($totalVariations, 1)) * 55);
                $product->update(['sync_progress' => $progress]);
            }

            // Find or create Color for this variation
            $colorCode = (string) ($variationData['itemColorCode'] ?? '');
            $colorName = is_array($variationData['itemWebColor'] ?? null)
                ? ($variationData['itemWebColor'][0] ?? '')
                : (string) ($variationData['itemWebColor'] ?? '');
            $color = null;

            if ($colorCode !== '' && $colorCode !== '0') {
                $color = $colorsCache->get($colorCode);

                if (! $color) {
                    $color = Color::create([
                        'color_code' => $colorCode,
                        'color_name' => $colorName ?: 'Color '.$colorCode,
                        'color_hex' => '#CCCCCC',
                    ]);
                    $colorsCache->put($colorCode, $color);
                }
            }

            if (empty($variationData['skus'])) {
                continue;
            }

            foreach ($variationData['skus'] as $item) {
                $actualAvailability = (int) $item['availability'];
                // Apply halving logic: floor(actual / 2)
                $halvedQuantity = (int) floor($actualAvailability / 2);

                // Find or create Size
                $sizeName = $item['skuSize']['webtext'] ?? null;
                $sizeCode = (string) ($item['skuSize']['size'] ?? '');
                $size = null;

                if ($sizeCode !== '') {
                    $size = $sizesCache->get($sizeCode);

                    if (! $size && $sizeName) {
                        $size = Size::create([
                            'size_code' => $sizeCode,
                            'size' => $sizeName,
                            'size_name' => $sizeName,
                        ]);
                        $sizesCache->put($sizeCode, $size);
                    }
                }

                if ($color && $size) {
                    $variationsToUpsert[] = [
                        'sku' => $item['sku'],
                        'product_id' => $product->id,
                        'color_id' => $color->id,
                        'size_id' => $size->id,
                        'quantity' => $halvedQuantity,
                        'is_available' => ($item['active'] ?? true) && $halvedQuantity > 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // 3. Batch upsert variations for high performance
        if ($variationsToUpsert !== []) {
            ProductVariation::upsert(
                $variationsToUpsert,
                ['sku'],
                ['color_id', 'size_id', 'quantity', 'is_available', 'updated_at']
            );
        }

        // Re-load variations
        $product->load([
            'variations.color',
            'variations.size',
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
                // Apply halving logic: floor(actual / 2)
                $halvedQuantity = (int) floor($actualAvailability / 2);

                $sku = $item['sku'];

                // We update quantity and availability flag based on sku.
                // Notice we do not upsert here, we simply update existing variations
                // because we only want to update availability, not create missing mappings.
                ProductVariation::where('product_id', $product->id)
                    ->where('sku', $sku)
                    ->update([
                        'quantity' => $halvedQuantity,
                        'is_available' => $halvedQuantity > 0,
                        'updated_at' => now(),
                    ]);
            }
        }

        // Touch the product so its updated_at is refreshed
        $product->touch();
    }
}
