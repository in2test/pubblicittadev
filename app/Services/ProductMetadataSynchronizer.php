<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;

class ProductMetadataSynchronizer
{
    /**
     * Synchronize basic product metadata (name, price, description) from API data.
     *
     * @param  array<string, mixed>  $data
     */
    public function sync(Product $product, array $data): void
    {
        if ($product->type !== Product::TYPE_NEWWAVE) {
            return;
        }

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

        $product->update($updateData);
    }
}
