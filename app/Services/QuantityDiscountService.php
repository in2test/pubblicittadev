<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\CategoryQuantityDiscount;

class QuantityDiscountService
{
    // Get discount by walking up the category tree from the given category up to root
    public function getDiscountForCategoryTree(?int $categoryId, int $quantity): ?CategoryQuantityDiscount
    {
        if (! $categoryId) {
            return null;
        }

        $path = $this->buildCategoryPath($categoryId);

        foreach ($path as $catId) {
            $discount = CategoryQuantityDiscount::where('category_id', $catId)
                ->where('min_quantity', '<=', $quantity)
                ->orderByDesc('min_quantity')
                ->orderByDesc('discount_value')
                ->first();
            if ($discount) {
                return $discount;
            }
        }

        return null;
    }

    // Build path from starting category up to root (closest to farthest)
    protected function buildCategoryPath(int $startCategoryId): array
    {
        $path = [];
        $current = Category::find($startCategoryId);
        while ($current && $current->parent) {
            $path[] = $current->parent->id;
            $current = $current->parent;
        }
        // Ensure we test starting category first
        array_unshift($path, $startCategoryId);

        return $path;
    }

    // Compute final price given a base price and a discount
    public function computeDiscountedPrice(float $basePrice, ?CategoryQuantityDiscount $discount): float
    {
        if (! $discount instanceof CategoryQuantityDiscount) {
            return $basePrice;
        }
        $value = (float) $discount->discount_value;
        $final = $discount->discount_type === 'percent' ? $basePrice * (1.0 - $value / 100.0) : $basePrice - $value;
        if ($final < 0) {
            $final = 0;
        }

        return (float) number_format($final, 2, '.', '');
    }
}
