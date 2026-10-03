<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\CategoryQuantityDiscount;
use App\Models\Product;
use Illuminate\Support\Collection;

class QuantityDiscountService
{
    /** @var array<int, Category|null> */
    protected static array $categories = [];

    /** @var array<int, array<int>> */
    protected static array $categoryPaths = [];

    /** @var array<string, CategoryQuantityDiscount|null> */
    protected static array $discounts = [];

    /**
     * Clear the static request-level caches.
     */
    public static function clearCache(): void
    {
        self::$categories = [];
        self::$categoryPaths = [];
        self::$discounts = [];
    }

    /**
     * Get a category by its ID, using static cache to prevent duplicate queries.
     */
    protected function getCategoryById(int $id): ?Category
    {
        if (! array_key_exists($id, self::$categories)) {
            self::$categories[$id] = Category::find($id);
        }

        return self::$categories[$id];
    }

    /**
     * Calculate the final price for a product based on its category and quantity.
     */
    public function calculatePrice(Product $product, int $quantity): float
    {
        $basePrice = (float) $product->price;
        $discount = $this->getDiscountForCategoryTree($product->category_id, $quantity);

        return $this->computeDiscountedPrice($basePrice, $discount);
    }

    /**
     * Get discount by walking up the category tree from the given category up to root.
     */
    public function getDiscountForCategoryTree(?int $categoryId, int $quantity): ?CategoryQuantityDiscount
    {
        if (! $categoryId) {
            return null;
        }

        $path = $this->buildCategoryPath($categoryId);
        $uncachedCategoryIds = collect($path)
            ->filter(fn (int $catId): bool => ! array_key_exists("{$catId}-{$quantity}", self::$discounts))
            ->values();

        if ($uncachedCategoryIds->isNotEmpty()) {
            $discountsByCategory = CategoryQuantityDiscount::query()
                ->whereIn('category_id', $uncachedCategoryIds)
                ->where('min_quantity', '<=', $quantity)
                ->orderByDesc('min_quantity')
                ->orderByDesc('discount_value')
                ->get()
                ->groupBy('category_id');

            foreach ($uncachedCategoryIds as $catId) {
                self::$discounts["{$catId}-{$quantity}"] = $discountsByCategory->get($catId)?->first();
            }
        }

        foreach ($path as $catId) {
            $cacheKey = "{$catId}-{$quantity}";
            $discount = self::$discounts[$cacheKey];
            if ($discount) {
                return $discount;
            }
        }

        return null;
    }

    /**
     * Public helper to retrieve the list of category IDs in the tree path, utilizing cache.
     *
     * @return array<int>
     */
    public function getCategoryPathIds(int $categoryId): array
    {
        return $this->buildCategoryPath($categoryId);
    }

    /**
     * @return Collection<int, CategoryQuantityDiscount>
     */
    public function getQuantityDiscountsForProduct(Product $product): Collection
    {
        if (! $product->category_id) {
            return collect();
        }

        $categoryIds = $this->getCategoryPathIds($product->category_id);

        return CategoryQuantityDiscount::whereIn('category_id', $categoryIds, 'and', false)
            ->where('min_quantity', '>', 1)
            ->orderBy('min_quantity', 'asc')
            ->get();
    }

    /**
     * Build path from starting category up to root (closest to farthest).
     *
     * @return array<int, int>
     */
    protected function buildCategoryPath(int $startCategoryId): array
    {
        if (isset(self::$categoryPaths[$startCategoryId])) {
            return self::$categoryPaths[$startCategoryId];
        }

        $path = [];
        $current = $this->getCategoryById($startCategoryId);
        while ($current instanceof Category && $current->parent_id) {
            $path[] = $current->parent_id;
            $current = $this->getCategoryById($current->parent_id);
        }
        // Ensure we test starting category first
        array_unshift($path, $startCategoryId);

        self::$categoryPaths[$startCategoryId] = $path;

        return $path;
    }

    /**
     * Compute final price given a base price and a discount.
     */
    public function computeDiscountedPrice(float $basePrice, ?CategoryQuantityDiscount $discount): float
    {
        if (! $discount instanceof CategoryQuantityDiscount) {
            return max(0.0, $basePrice);
        }
        $value = (float) $discount->discount_value;
        $final = $discount->discount_type === 'percent' ? $basePrice * (1.0 - $value / 100.0) : $basePrice - $value;
        if ($final < 0) {
            $final = 0;
        }

        return (float) number_format($final, 2, '.', '');
    }
}
