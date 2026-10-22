<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Collection as ProductCollection;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Builds the index of the other products that belong to the same collections
 * as the product being displayed.
 *
 * A product can be part of more than one collection, so the result keeps one
 * entry per collection, each carrying the sibling products of that collection.
 */
class ProductCollectionIndexService
{
    /**
     * How many sibling products are listed for each collection.
     */
    private const PRODUCTS_PER_COLLECTION = 8;

    /**
     * The active collections the product belongs to, each with its sibling products.
     *
     * @return Collection<int, ProductCollection>
     */
    public function forProduct(Product $product): Collection
    {
        $collections = $product->collections()
            ->where('collections.is_active', true)
            ->withCount([
                'products as siblings_count' => fn ($query) => $query
                    ->where('products.id', '!=', $product->id)
                    ->where('products.is_active', true),
            ])
            ->with([
                'products' => fn ($query) => $query
                    ->where('products.id', '!=', $product->id)
                    ->where('products.is_active', true)
                    ->reorder('collection_product.sort_order')
                    ->orderBy('products.id')
                    ->limit(self::PRODUCTS_PER_COLLECTION)
                    ->with($this->siblingRelations()),
            ])
            ->get();

        return $collections
            ->filter(fn (ProductCollection $collection): bool => $collection->products->isNotEmpty())
            ->values();
    }

    /**
     * The relations the product card reads, loaded in one go to keep the number
     * of queries constant regardless of how many sibling products are listed.
     *
     * The pricing relations are loaded with all their columns: the pricing
     * services read `max_quantity` and `product_sku_id` to pick the tier that
     * matches a quantity, so narrowing the select would silently price the
     * sibling products with the wrong tier.
     *
     * @return array<int|string, mixed>
     */
    private function siblingRelations(): array
    {
        return [
            'category:id,name,slug',
            'brand_relation:id,name',
            'media' => fn ($query) => $query->orderBy('order_column')->limit(1),
            'images' => fn ($query) => $query->orderBy('order_by')->limit(1),
            'pricingTiers',
            'skus',
            'variationTypes',
            'productVariationTypes.options.option',
        ];
    }
}
