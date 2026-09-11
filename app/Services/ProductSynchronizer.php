<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariationType;

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

        // Ensure global Color and Size variation types exist
        $metaService = app(ProductMetaSyncService::class);
        $types = $metaService->ensureVariationTypes();

        // Sync metadata and ensure color options exist (NewWave products only)
        $colorOptionsCache = $metaService->ensureColorOptions($data, $types['color']->id);
        $metaService->syncMeta($product, $data);

        // Sync remote images
        app(ProductImageSyncService::class)->syncImages($product, $data, $colorOptionsCache);

        $product->update(['sync_progress' => 40]);

        if (empty($data['variations'])) {
            return;
        }

        // Attach product-level variation type records
        $productColorType = ProductVariationType::firstOrCreate([
            'product_id' => $product->id,
            'variation_type_id' => $types['color']->id,
        ], [
            'has_images' => true,
        ]);

        $productSizeType = ProductVariationType::firstOrCreate([
            'product_id' => $product->id,
            'variation_type_id' => $types['size']->id,
        ], [
            'has_images' => false,
        ]);

        // Sync SKUs and variation option assignments
        app(ProductSkuSyncService::class)->syncSkus(
            $product,
            $data,
            $colorOptionsCache,
            $productColorType,
            $productSizeType
        );
    }

    /**
     * Fast synchronization of just the quantities for all variations.
     */
    public function syncAvailability(Product $product): void
    {
        app(ProductAvailabilitySynchronizer::class)->sync($product);
    }
}
