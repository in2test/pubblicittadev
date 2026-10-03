<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\VariationOption;
use App\Models\VariationType;
use Carbon\CarbonImmutable;

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

        $this->metadataSynchronizer->sync($product, $data);

        // Pre-cache color options
        $colorOptionsCache = $colorType->options()->get()->keyBy('value');
        $newColorOptions = [];
        foreach ($data['variations'] ?? [] as $variationData) {
            $variationColorCode = (string) ($variationData['itemColorCode'] ?? '');
            if ($variationColorCode === '' || $variationColorCode === '0' || $colorOptionsCache->has($variationColorCode)) {
                continue;
            }

            $variationColorName = is_array($variationData['itemWebColor'] ?? null)
                ? ($variationData['itemWebColor'][0] ?? '')
                : (string) ($variationData['itemWebColor'] ?? '');

            $newColorOptions[$variationColorCode] ??= [
                'variation_type_id' => $colorType->id,
                'name' => $variationColorName ?: 'Color '.$variationColorCode,
                'value' => $variationColorCode,
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ];
        }

        if ($newColorOptions !== []) {
            VariationOption::query()->insert(array_values($newColorOptions));
            $insertedColorOptions = VariationOption::query()
                ->where('variation_type_id', $colorType->id)
                ->whereIn('value', array_keys($newColorOptions))
                ->get()
                ->keyBy('value');

            foreach ($insertedColorOptions as $value => $colorOption) {
                $colorOptionsCache->put($value, $colorOption);
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
