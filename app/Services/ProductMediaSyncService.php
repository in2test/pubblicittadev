<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Image;
use App\Models\Product;

class ProductMediaSyncService
{
    /**
     * Synchronize local media and remote_images JSON with remote image records in the database.
     */
    public function syncLocalMediaToImageRecords(Product $product): void
    {
        // NewWave stores the remote catalogue images as JSON on the product.
        $remoteImages = $product->remote_images ?? [];

        $imageRows = [];
        foreach ($remoteImages as $remote) {
            // Accept both the current `url` key and the legacy `image_url` key.
            $url = $remote['url'] ?? $remote['image_url'] ?? null;

            // Ignore incomplete remote-image entries instead of creating unusable records.
            if (! $url) {
                continue;
            }

            $imageRows[$url] = [
                'product_id' => $product->id,
                'image_url' => $url,
                'image_description' => $remote['image_description'] ?? null,
                'variation_option_id' => $remote['variation_option_id'] ?? null,
            ];
        }

        if ($imageRows === []) {
            return;
        }

        $existingImages = Image::query()
            ->where('product_id', $product->id)
            ->whereIn('image_url', array_keys($imageRows))
            ->get()
            ->keyBy('image_url');

        foreach ($imageRows as $url => $imageRow) {
            $imageRows[$url]['id'] = $existingImages->get($url)?->id;
        }

        Image::query()->upsert(array_values($imageRows), ['id'], [
            'image_description',
            'variation_option_id',
            'updated_at',
        ]);
    }
}
