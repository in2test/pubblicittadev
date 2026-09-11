<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Image;
use App\Models\Product;
use App\Models\VariationOption;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Log;

/**
 * Synchronizes remote product images from the NewWave API into the images table.
 */
class ProductImageSyncService
{
    /**
     * Persist remote image records for the given product from the API payload.
     *
     * Processes both top-level product images and per-variation images.
     * Creates or updates Image records, linking each to the correct color VariationOption.
     *
     * @param  array<string, mixed>  $data  Full API payload containing 'pictures' and 'variations'.
     * @param  EloquentCollection<int, VariationOption>  $colorOptionsCache  Pre-loaded color options keyed by value.
     */
    public function syncImages(Product $product, array $data, EloquentCollection $colorOptionsCache): void
    {
        $remoteImagesArray = [];

        if (! empty($data['pictures'])) {
            foreach ($data['pictures'] as $idx => $img) {
                $url = $img['standardUrl'] ?? '';
                if ($url) {
                    $remoteImagesArray[] = [
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
                    foreach ($v['pictures'] as $vImg) {
                        $url = $vImg['standardUrl'] ?? '';
                        if ($url) {
                            $remoteImagesArray[] = [
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
    }
}
