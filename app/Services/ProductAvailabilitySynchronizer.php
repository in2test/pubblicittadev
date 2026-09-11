<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSku;

class ProductAvailabilitySynchronizer
{
    public function __construct(private readonly NwgApiClient $apiClient) {}

    /**
     * Synchronize only stock quantities for an existing NewWave product.
     */
    public function sync(Product $product): void
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
                $quantity = (int) floor(((int) $item['availability']) / 2);

                ProductSku::where('product_id', $product->id)
                    ->where('sku', $item['sku'])
                    ->update([
                        'quantity' => $quantity,
                        'is_available' => $quantity > 0,
                        'updated_at' => now(),
                    ]);
            }
        }

        $product->touch();
    }
}
