<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ProductVariationType;
use App\Models\VariationOption;
use Illuminate\Database\Eloquent\Builder;

class ProductVariantResolver
{
    /**
     * Get the active SKU based on the provided selected options.
     */
    public function getActiveSku(Product $product, array $selectedOptions): ?ProductSku
    {
        $product->loadMissing(['skus.options', 'variationTypes']);

        return $product->skus->first(function ($sku) use ($product, $selectedOptions): bool {
            foreach ($product->variationTypes as $type) {
                /** @var ProductVariationType|null $pivot */
                $pivot = $type->pivot;

                if ($pivot && $pivot->is_modifier) {
                    continue;
                }

                $selectedId = $selectedOptions[$type->id] ?? null;

                if ($selectedId && $selectedId != 999999 && ! $sku->options->contains('id', $selectedId)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Finds the nearest existing format option ID for the provided custom dimensions.
     */
    public function getNearestFormatOptionId(Product $product, float $width, float $height): ?int
    {
        $product->loadMissing('variationTypes');

        $formatType = $product->variationTypes->firstWhere('name', 'Formato');
        if (! $formatType) {
            return null;
        }

        /** @var ProductVariationType|null $pvt */
        $pvt = $product->productVariationTypes()->where('variation_type_id', $formatType->id)->first();
        if (! $pvt) {
            return null;
        }

        $options = VariationOption::whereHas('productVariationOptions', function (Builder $query) use ($pvt) {
            $query->where('product_variation_type_id', $pvt->id);
        })->get();

        $nearestOptionId = null;
        $minDistance = null;

        $customMin = min($width, $height);
        $customMax = max($width, $height);

        foreach ($options as $opt) {
            if ($opt->id == 999999) {
                continue;
            }

            $name = strtolower((string) $opt->name);
            if (str_contains($name, 'personalizzato') || str_contains($name, 'custom')) {
                continue;
            }

            if (preg_match('/(\d+(?:[.,]\d+)?)\s*[xX]\s*(\d+(?:[.,]\d+)?)/', $name, $matches)) {
                $parsedW = (float) str_replace(',', '.', $matches[1]);
                $parsedH = (float) str_replace(',', '.', $matches[2]);

                if (str_contains(strtolower($name), 'cm')) {
                    $parsedW *= 10;
                    $parsedH *= 10;
                }

                $optMin = min($parsedW, $parsedH);
                $optMax = max($parsedW, $parsedH);
                $distance = sqrt(($customMin - $optMin) ** 2 + ($customMax - $optMax) ** 2);

                if ($minDistance === null || $distance < $minDistance) {
                    $minDistance = $distance;
                    $nearestOptionId = $opt->id;
                }
            }
        }

        return $nearestOptionId;
    }
}
