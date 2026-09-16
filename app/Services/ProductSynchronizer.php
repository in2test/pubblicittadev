<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\VariationOption;
use App\Models\VariationType;

class ProductSynchronizer
{
    private readonly ProductMetadataSynchronizer $metadataSynchronizer;

    private readonly ProductImageSynchronizer $imageSynchronizer;

    private readonly ProductSkuSynchronizer $skuSynchronizer;

    private readonly ProductAvailabilitySynchronizer $availabilitySynchronizer;

    public function __construct(
        private readonly NwgApiClient $apiClient,
        ?ProductMetadataSynchronizer $metadataSynchronizer = null,
        ?ProductImageSynchronizer $imageSynchronizer = null,
        ?ProductSkuSynchronizer $skuSynchronizer = null,
        ?ProductAvailabilitySynchronizer $availabilitySynchronizer = null,
    ) {
        $this->metadataSynchronizer = $metadataSynchronizer ?? app(ProductMetadataSynchronizer::class);
        $this->imageSynchronizer = $imageSynchronizer ?? app(ProductImageSynchronizer::class);
        $this->skuSynchronizer = $skuSynchronizer ?? app(ProductSkuSynchronizer::class);
        $this->availabilitySynchronizer = $availabilitySynchronizer ?? app(ProductAvailabilitySynchronizer::class);
    }

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

        // Get or Create generic variation types for Color and Size
        $colorType = VariationType::firstOrCreate(
            ['name' => 'Colore'],
            ['presentation_type' => 'color_swatch']
        );

        $sizeType = VariationType::firstOrCreate(
            ['name' => 'Taglia'],
            ['presentation_type' => 'radio']
        );

        // Synchronize metadata
        $this->metadataSynchronizer->sync($product, $data);

        // Pre-cache color options
        $colorOptionsCache = $colorType->options()->get()->keyBy('value');
        if (! empty($data['variations'])) {
            foreach ($data['variations'] as $variationData) {
                $variationColorCode = (string) ($variationData['itemColorCode'] ?? '');
                if ($variationColorCode !== '' && $variationColorCode !== '0' && ! $colorOptionsCache->has($variationColorCode)) {
                    $variationColorName = is_array($variationData['itemWebColor'] ?? null)
                        ? ($variationData['itemWebColor'][0] ?? '')
                        : (string) ($variationData['itemWebColor'] ?? '');

                    $colorOptionsCache->put($variationColorCode, VariationOption::create([
                        'variation_type_id' => $colorType->id,
                        'name' => $variationColorName ?: 'Color '.$variationColorCode,
                        'value' => $variationColorCode,
                    ]));
                }
            }
        }

        // Synchronize remote images
        $this->imageSynchronizer->sync($product, $data, $colorOptionsCache);

        $product->update(['sync_progress' => 40]);

        // Synchronize variation types, options, SKUs, and pivot records
        $this->skuSynchronizer->sync($product, $data, $colorType, $sizeType, $colorOptionsCache);

        // Re-load variations with nested option relation
        $product->load([
            'variationTypes',
            'skus.options.type',
        ]);
    }

    /**
     * Fast synchronization of just the quantities for all variations.
     */
    public function syncAvailability(Product $product): void
    {
        $this->availabilitySynchronizer->sync($product);
    }
}
