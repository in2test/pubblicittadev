<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariationType;
use Illuminate\Support\Collection;

/**
 * ProductSynchronizer now acts as the central coordinator, delegating specific synchronization
 * tasks to dedicated, single-responsibility services.
 */
class ProductSynchronizer
{
    public function __construct(
        private readonly NwgApiClient $apiClient,
        private readonly ProductMetadataSyncService $metadataService,
        private readonly ProductImageSyncService $imageService,
        private readonly ProductSkuSyncService $skuService,
        private readonly ProductAvailabilitySynchronizer $availabilityService
    ) {}

    /**
     * Synchronize product variations in the database with the API data.
     */
    public function syncProduct(Product $product): void
    {
        $data = $this->apiClient->getFullProductData($product->sku);

        if (! $data) {
            return;
        }

        // --- STAGE 1: Metadata and Base Types ---
        $product->update(['sync_progress' => 10]);

        // This service handles the logic for ensuring base types (Color/Size) exist
        $metadataService->ensureBaseTypes();

        // Sync metadata and ensure color options exist
        $colorOptionsCache = $metadataService->ensureColorOptions($data, ProductVariationType::firstWhere('name', 'Color')?->id);
        $metadataService->syncMeta($product, $data);

        $product->update(['sync_progress' => 40]);

        // --- STAGE 2: Image Synchronization ---
        $this->imageService->syncImages($product, $data, $colorOptionsCache);

        // --- STAGE 3: SKUs and Variations ---
        if (empty($data['variations'])) {
            return;
        }

        $this->skuService->syncSkus(
            $product,
            $data,
            $colorOptionsCache
        );

        $product->update(['sync_progress' => 80]);


        // --- STAGE 4: Availability Sync ---
        $this->availabilityService->sync($product);

        $product->update(['sync_progress' => 100, 'synced_at' => now()]);
    }

    /**
     * Fast synchronization of just the quantities for all variations.
     */
    public function syncAvailability(Product $product): void
    {
        $this->availabilityService->sync($product);
    }
}
