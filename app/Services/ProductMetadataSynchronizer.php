<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Support\NewWaveProductAttributes;

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

        // New fields from NewWave API formatted in Italian
        if (isset($data['productFabrics'])) {
            $updateData['technical_specs'] = NewWaveProductAttributes::formatFabrics((string) $data['productFabrics']) ?? $product->technical_specs;
        }

        if (isset($data['productFeature']) && is_array($data['productFeature'])) {
            $features = NewWaveProductAttributes::formatFeatures($data['productFeature']);
            $updateData['construction_features'] = $features !== [] ? implode(', ', $features) : $product->construction_features;
        }

        if (isset($data['productCertifications']) && is_array($data['productCertifications'])) {
            $certifications = NewWaveProductAttributes::formatCertifications($data['productCertifications']);
            $updateData['certifications'] = $certifications !== [] ? implode(', ', $certifications) : $product->certifications;
        }

        if (isset($data['productCareInstructions']) && is_array($data['productCareInstructions'])) {
            $careInstructions = NewWaveProductAttributes::formatCareInstructions($data['productCareInstructions']);
            $updateData['customization_notes'] = $careInstructions !== [] ? implode(', ', $careInstructions) : $product->customization_notes;
        }

        $updateData['sync_progress'] = 20;

        $product->update($updateData);
    }
}
