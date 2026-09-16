<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Image;
use App\Models\Product;
use App\Models\VariationOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProductImageSynchronizer
{
    /**
     * Synchronize remote images from API data into the images table.
     *
     * @param  array<string, mixed>  $data
     * @param  Collection<string, VariationOption>  $colorOptionsCache
     */
    public function sync(Product $product, array $data, Collection $colorOptionsCache): void
    {
        if ($product->type !== Product::TYPE_NEWWAVE) {
            return;
        }

        $remoteImagesArray = [];

        // Top-level product pictures
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

        // Variation-level pictures
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
    }
}
