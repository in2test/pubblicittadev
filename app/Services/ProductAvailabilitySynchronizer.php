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

        /** @var array<string, int> $availabilityBySku */
        $availabilityBySku = [];

        foreach ($data['variations'] as $variationData) {
            if (empty($variationData['skus'])) {
                continue;
            }

            foreach ($variationData['skus'] as $item) {
                $availabilityBySku[(string) $item['sku']] = (int) floor(((int) $item['availability']) / 2);
            }
        }

        if ($availabilityBySku !== []) {
            $existingSkus = ProductSku::query()
                ->where('product_id', $product->id)
                ->whereIn('sku', array_keys($availabilityBySku))
                ->get(['id', 'product_id', 'sku'])
                ->keyBy('sku');

            $updates = [];
            $updatedAt = now();

            foreach ($availabilityBySku as $sku => $quantity) {
                $existingSku = $existingSkus->get($sku);

                if (! $existingSku) {
                    continue;
                }

                $updates[] = [
                    'id' => $existingSku->id,
                    'product_id' => $existingSku->product_id,
                    'sku' => $existingSku->sku,
                    'quantity' => $quantity,
                    'is_available' => $quantity > 0,
                    'updated_at' => $updatedAt,
                ];
            }

            if ($updates !== []) {
                ProductSku::upsert($updates, ['id'], ['quantity', 'is_available', 'updated_at']);
            }
        }

        $product->touch();
    }
}
