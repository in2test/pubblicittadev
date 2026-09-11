<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Synchronizes product metadata (name, price, description) from the NewWave API.
 *
 * Only operates on products of type NewWave. Standard products are skipped.
 */
class ProductMetaSyncService
{
    /**
     * Update product metadata fields from the given API payload.
     *
     * @param  array<string, mixed>  $data  Full product payload from the API.
     */
    public function syncMeta(Product $product, array $data): void
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

    /**
     * Ensure all color variation options referenced in the API payload exist in the database.
     *
     * @param  array<string, mixed>  $data
     * @return EloquentCollection<int, VariationOption> Cache keyed by color code.
     */
    public function ensureColorOptions(array $data, int $colorTypeId): EloquentCollection
    {
        $colorOptionsCache = VariationOption::where('variation_type_id', $colorTypeId)->get()->keyBy('value');

        if (empty($data['variations'])) {
            return $colorOptionsCache;
        }

        foreach ($data['variations'] as $variationData) {
            $variationColorCode = (string) ($variationData['itemColorCode'] ?? '');
            if ($variationColorCode !== '' && $variationColorCode !== '0' && ! $colorOptionsCache->has($variationColorCode)) {
                $variationColorName = is_array($variationData['itemWebColor'] ?? null)
                    ? ($variationData['itemWebColor'][0] ?? '')
                    : (string) ($variationData['itemWebColor'] ?? '');

                $colorOptionsCache->put($variationColorCode, VariationOption::create([
                    'variation_type_id' => $colorTypeId,
                    'name' => $variationColorName ?: 'Color '.$variationColorCode,
                    'value' => $variationColorCode,
                ]));
            }
        }

        return $colorOptionsCache;
    }

    /**
     * Get or create the global Color and Size variation types.
     *
     * @return array{color: VariationType, size: VariationType}
     */
    public function ensureVariationTypes(): array
    {
        $colorType = VariationType::firstOrCreate(
            ['name' => 'Colore'],
            ['presentation_type' => 'color_swatch']
        );

        $sizeType = VariationType::firstOrCreate(
            ['name' => 'Taglia'],
            ['presentation_type' => 'radio']
        );

        return ['color' => $colorType, 'size' => $sizeType];
    }
}
