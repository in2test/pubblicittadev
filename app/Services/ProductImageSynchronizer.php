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

        $remoteImages = [
            ...$this->collectProductPictures($product, $data['pictures'] ?? []),
            ...$this->collectVariationPictures($data['variations'] ?? [], $colorOptionsCache),
        ];

        $this->persistRemoteImages($product, $remoteImages);
    }

    /**
     * @param  array<int, array<string, mixed>>  $pictures
     * @return array<int, array<string, mixed>>
     */
    private function collectProductPictures(Product $product, array $pictures): array
    {
        $remoteImages = [];

        foreach ($pictures as $index => $picture) {
            $url = $picture['standardUrl'] ?? '';
            if (! $url) {
                continue;
            }

            $remoteImages[] = [
                'id' => 'top_'.$index,
                'url' => $url,
                'thumb' => $picture['thumbnailUrl'] ?? '',
                'medium' => $picture['largeThumbnailUrl'] ?? '',
                'large' => $picture['standardUrl'] ?? '',
                'variation_option_id' => null,
            ];

            Log::info("Remote image for SKU {$product->sku}: {$url}");
        }

        return $remoteImages;
    }

    /**
     * @param  array<int, array<string, mixed>>  $variations
     * @param  Collection<string, VariationOption>  $colorOptionsCache
     * @return array<int, array<string, mixed>>
     */
    private function collectVariationPictures(array $variations, Collection $colorOptionsCache): array
    {
        $remoteImages = [];

        foreach ($variations as $variation) {
            $colorCode = (string) ($variation['itemColorCode'] ?? '');
            /** @var VariationOption|null $colorOption */
            $colorOption = $colorOptionsCache->get($colorCode);

            foreach ($variation['pictures'] ?? [] as $index => $picture) {
                $url = $picture['standardUrl'] ?? '';
                if (! $url) {
                    continue;
                }

                $remoteImages[] = [
                    'id' => 'var_'.($colorCode ?: 'nc').'_'.$index,
                    'url' => $url,
                    'thumb' => $picture['thumbnailUrl'] ?? '',
                    'medium' => $picture['largeThumbnailUrl'] ?? '',
                    'large' => $picture['standardUrl'] ?? '',
                    'variation_option_id' => $colorOption?->id,
                ];
            }
        }

        return $remoteImages;
    }

    /**
     * @param  array<int, array<string, mixed>>  $remoteImages
     */
    private function persistRemoteImages(Product $product, array $remoteImages): void
    {
        if ($remoteImages === []) {
            return;
        }

        $imageUrls = array_column($remoteImages, 'url');
        $existingImages = Image::query()
            ->where('product_id', $product->id)
            ->whereIn('image_url', $imageUrls)
            ->get()
            ->keyBy(fn (Image $image): string => serialize([$image->image_url, $image->variation_option_id]));

        $rows = [];
        $remoteImageOrder = 0;
        foreach ($remoteImages as $image) {
            $identity = serialize([$image['url'], $image['variation_option_id']]);
            $rows[$identity] = [
                'id' => $existingImages->get($identity)?->id,
                'product_id' => $product->id,
                'image_url' => $image['url'],
                'variation_option_id' => $image['variation_option_id'],
                'order_by' => $remoteImageOrder++,
                'image_description' => $product->name,
                'thumbnail_url' => $image['thumb'] ?: null,
                'medium_url' => $image['medium'] ?: null,
                'large_url' => $image['large'] ?: null,
            ];
            Log::info("Caching remote image for SKU {$product->sku}: {$image['url']}");
        }

        Image::query()->upsert(array_values($rows), ['id'], [
            'order_by',
            'image_description',
            'thumbnail_url',
            'medium_url',
            'large_url',
            'updated_at',
        ]);

        Log::info('Cached '.count($remoteImages)." remote images to 'images' table for SKU {$product->sku}");
    }
}
