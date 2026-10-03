<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariationOption;
use App\Models\ProductVariationType;
use App\Models\VariationOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductVariationDisplayService
{
    /**
     * @return array{display: Collection<int, VariationOption>, remaining: int, total: int}
     */
    public function getPreviewColors(Product $product, int $limit = 8): array
    {
        if ($product->relationLoaded('productVariationTypes')) {
            $productVariationType = $product->productVariationTypes->firstWhere('has_images', true);
            if (! $productVariationType) {
                return ['display' => collect(), 'remaining' => 0, 'total' => 0];
            }
        } else {
            $visualType = $product->variationTypes()
                ->wherePivot('has_images', true)
                ->first();
            if (! $visualType) {
                return ['display' => collect(), 'remaining' => 0, 'total' => 0];
            }

            $productVariationType = ProductVariationType::query()
                ->where('product_id', $product->id)
                ->where('variation_type_id', $visualType->id)
                ->first();
        }

        if (! $productVariationType instanceof ProductVariationType) {
            return ['display' => collect(), 'remaining' => 0, 'total' => 0];
        }

        if ($productVariationType->relationLoaded('options')) {
            $options = $productVariationType->options
                ->map(fn (ProductVariationOption $productVariationOption) => $productVariationOption->relationLoaded('option')
                    ? $productVariationOption->option
                    : null)
                ->filter()
                ->sortBy('sort_order')
                ->values();
        } else {
            $productVariationTypeId = $productVariationType->id;
            $options = VariationOption::query()
                ->whereHas('productVariationOptions', function (Builder $query) use ($productVariationTypeId) {
                    $query->where('product_variation_type_id', $productVariationTypeId);
                })
                ->orderBy('sort_order')
                ->get();
        }

        return [
            'display' => $options->take($limit),
            'remaining' => max(0, $options->count() - $limit),
            'total' => $options->count(),
        ];
    }

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
