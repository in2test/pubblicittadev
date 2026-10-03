<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariationOption;
use App\Models\VariationOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductVariationDisplayService
{
    public function getColorOptions(Product $product): string
    {
        $colorVariationTypes = $product->variationTypes()
            ->where(function (Builder $query) {
                $query->where('presentation_type', 'color_swatch')
                    ->orWhere('name', 'like', '%color%');
            })
            ->orWherePivot('has_images', true)
            ->with('options')
            ->get();

        return $colorVariationTypes
            ->pluck('options')
            ->flatten()
            ->pluck('name')
            ->unique()
            ->sort()
            ->join(', ');
    }

    public function getVariationOptionFromRequest(Product $product, ?Request $request = null): ?VariationOption
    {
        $request ??= request();

        if (empty($request->query())) {
            return null;
        }

        $product->loadMissing([
            'variationTypes',
            'productVariationTypes.options.option',
        ]);

        foreach ($product->variationTypes as $type) {
            $slug = Str::slug($type->name);
            $value = $request->query($slug) ?? $request->query($type->name) ?? $request->query(strtolower($type->name));
            if ($value === null || $value === '') {
                continue;
            }

            $productVariationType = $product->productVariationTypes->firstWhere('variation_type_id', $type->id);
            if (! $productVariationType) {
                continue;
            }

            $options = $productVariationType->options
                ->map(fn (ProductVariationOption $productVariationOption) => $productVariationOption->relationLoaded('option')
                    ? $productVariationOption->option
                    : $productVariationOption->option()->first())
                ->filter();

            $valueString = (string) $value;
            $matchedOption = $options->first(fn (VariationOption $option) => (string) $option->id === $valueString
                || (string) $option->value === $valueString
                || (string) $option->name === $valueString
                || Str::slug((string) $option->name) === Str::slug($valueString)
                || Str::slug((string) $option->value) === Str::slug($valueString));

            if ($matchedOption && $product->getImagesForOption($matchedOption->id)->isNotEmpty()) {
                return $matchedOption;
            }
        }

        return null;
    }

    public function getVariationAttributeValue(Product $product, string $variationTypeName): ?string
    {
        $type = $product->variationTypes->firstWhere('name', $variationTypeName);
        if (! $type) {
            return null;
        }

        $productVariationType = $product->productVariationTypes->firstWhere('variation_type_id', $type->id);
        if (! $productVariationType) {
            return null;
        }

        return $productVariationType->options()->first()?->option?->value;
    }
}
