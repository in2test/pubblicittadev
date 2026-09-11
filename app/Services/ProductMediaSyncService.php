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

        foreach ($remoteImages as $remote) {
            // Accept both the current `url` key and the legacy `image_url` key.
            $url = $remote['url'] ?? $remote['image_url'] ?? null;

            // Ignore incomplete remote-image entries instead of creating unusable records.
            if (! $url) {
                continue;
            }

            // The product and URL form the identity, making repeated syncs idempotent.
            Image::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'image_url' => $url,
                ],
                [
                    // Refresh descriptive and variation data when the remote payload changes.
                    'image_description' => $remote['image_description'] ?? null,
                    'variation_option_id' => $remote['variation_option_id'] ?? null,
                ]
            );
        }
    }
}
