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
 * The search semantics are word based: the query is split on whitespace and
 * every word must match, on its own, one of the searchable columns. This is
 * what the catalog page does, and the header search box uses the same service
 * so both surfaces always return the same products.
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
        foreach ($this->splitIntoWords($term) as $word) {
            $query->where(function (Builder $scoped) use ($word): void {
                $scoped->where('name', 'like', "%{$word}%")
                    ->orWhere('sku', 'like', "%{$word}%")
                    ->orWhere('description', 'like', "%{$word}%")
                    ->orWhereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('name', 'like', "%{$word}%"))
                    ->orWhereHas('brand_relation', fn (Builder $brandQuery) => $brandQuery->where('name', 'like', "%{$word}%"));
            });
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

        $this->applySearchTerm($query, $term);

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
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
