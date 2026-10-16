<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Arr;

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

        // New fields from NewWave API
        $updateData['material'] = $data['productFabrics'] ?? $product->material;

        if (isset($data['productFeature']) && is_array($data['productFeature'])) {
            $updateData['construction_features'] = implode(', ', array_column($data['productFeature'], 'value')) ?? $product->construction_features;
        }

        if (isset($data['productCertifications']) && is_array($data['productCertifications'])) {
            $updateData['certifications'] = implode(', ', array_column($data['productCertifications'], 'value')) ?? $product->certifications;
        }

        if (isset($data['productCareInstructions']) && is_array($data['productCareInstructions'])) {
            $updateData['customization_notes'] = implode(', ', array_column($data['productCareInstructions'], 'value')) ?? $product->customization_notes;
        }

        $updateData['sync_progress'] = 20;

        $product->update($updateData);
    }
}
