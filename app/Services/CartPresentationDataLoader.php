<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSku;
use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class CartPresentationDataLoader
{
    /**
     * @param  Collection<string, array<string, mixed>>  $rawItems
     * @param  Collection<int, Product>  $products
     * @return array{
     *     skus: Collection<int, ProductSku>,
     *     options: Collection<int, VariationOption>,
     *     types: Collection<int, VariationType>
     * }
     */
    public function load(Collection $rawItems, Collection $products): array
    {
        $skuIds = $rawItems->pluck('quantities')
            ->filter(fn ($quantities) => is_array($quantities))
            ->flatMap(fn (array $quantities) => array_keys($quantities))
            ->unique();

        $skus = ProductSku::with('options')
            ->whereIn('id', $skuIds)
            ->get()
            ->keyBy('id');

        $optionIds = $this->relatedOptionIds($rawItems, $products);
        $options = $optionIds === []
            ? collect()
            : VariationOption::whereIn('id', $optionIds)->get()->keyBy('id');

        $typeIds = $options->pluck('variation_type_id')->all();
        foreach ($products as $product) {
            foreach ($product->productVariationTypes as $pivot) {
                $typeIds[] = (int) $pivot->variation_type_id;
            }
        }

        $typeIds = array_unique($typeIds);
        $types = $typeIds === []
            ? collect()
            : VariationType::whereIn('id', $typeIds)->get()->keyBy('id');

        return [
            'skus' => $skus,
            'options' => $options,
            'types' => $types,
        ];
    }

    /**
     * @param  Collection<string, array<string, mixed>>  $rawItems
     * @param  Collection<int, Product>  $products
     * @return array<int, int>
     */
    private function relatedOptionIds(Collection $rawItems, Collection $products): array
    {
        $optionIds = $rawItems->pluck('selected_options')
            ->filter(fn ($selectedOptions) => is_array($selectedOptions))
            ->flatMap(fn (array $selectedOptions) => Arr::flatten($selectedOptions))
            ->all();

        foreach ($products as $product) {
            foreach ($product->productVariationTypes as $pivot) {
                foreach ($pivot->options as $option) {
                    $optionIds[] = (int) $option->variation_option_id;
                }
            }
        }

        return array_values(array_unique($optionIds));
    }
}
