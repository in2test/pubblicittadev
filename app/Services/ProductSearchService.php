<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Single source of truth for product search.
 *
 * Search is backed by Laravel Scout: each search word is handed to
 * ``Product::search()`` and the resulting matching IDs are then applied to
 * whatever Eloquent builder the caller is already working on. The per-word
 * results are intersected, so every word of the term must match. Catalog
 * filters (category, brand, variations, active flag, ...) stay intact because
 * Scout only decides which products match.
 */
class ProductSearchService
{
    /**
     * Build a query matching every word of the search term.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function applySearchTerm(Builder $query, ?string $term): Builder
    {
        $words = $this->splitIntoWords($term);

        if ($words === []) {
            return $query;
        }

        $ids = $this->resolveIds($words);

        if ($ids === []) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('id', $ids);
        }

        return $query;
    }

    /**
     * Search the products visible to the given user.
     *
     * @param  Builder<Product>|null  $query
     * @return Collection<int, Product>
     */
    public function search(?string $term, ?Builder $query = null, ?int $limit = null): Collection
    {
        $query ??= Product::query();

        $words = $this->splitIntoWords($term);

        if ($words !== []) {
            $ids = $this->resolveIds($words);

            if ($ids === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $ids);
            }
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Resolve the product IDs matching every word of the term.
     *
     * @param  array<int, string>  $words
     * @return array<int, int>
     */
    protected function resolveIds(array $words): array
    {
        $matchingSets = [];

        $products = Product::with(['category:id,name', 'brand_relation:id,name'])->get();

        foreach ($words as $word) {
            $lowerWord = Str::lower($word);

            $ids = collect(Product::search($word)->keys());

            $extraIds = $products->filter(
                fn (Product $product): bool => str_contains(Str::lower((string) $product->category->name), $lowerWord)
                    || str_contains(Str::lower((string) $product->brand_relation?->name), $lowerWord)
            )->pluck('id');

            $ids = $ids->merge($extraIds);

            $matchingSets[] = $ids->map(fn ($id) => (int) $id)->unique()->values()->all();
        }

        if ($matchingSets === []) {
            return [];
        }

        $result = $matchingSets[0];

        foreach (array_slice($matchingSets, 1) as $set) {
            $result = array_values(array_intersect($result, $set));
        }

        return $result;
    }

    /**
     * Split a search term into the words that must all match.
     *
     * @return array<int, string>
     */
    public function splitIntoWords(?string $term): array
    {
        $term = Str::of((string) $term)->trim()->toString();

        if ($term === '' || $term === '0') {
            return [];
        }

        return preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
