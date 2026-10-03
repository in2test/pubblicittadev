<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryQuantityDiscount;
use App\Models\Product;
use App\Services\QuantityDiscountService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    QuantityDiscountService::clearCache();
});

it('calculates the correct discounted price for a product', function () {
    $service = app(QuantityDiscountService::class);
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 100.00,
    ]);

    CategoryQuantityDiscount::create([
        'category_id' => $category->id,
        'min_quantity' => 10,
        'discount_type' => 'percent',
        'discount_value' => 10, // 10%
    ]);

    // Quantity below threshold
    QuantityDiscountService::clearCache();
    $product = $product->fresh();
    expect($service->calculatePrice($product, 5))->toBe(100.00);

    // Quantity at threshold
    expect($service->calculatePrice($product, 10))->toBe(90.00);

    // Quantity above threshold
    expect($service->calculatePrice($product, 20))->toBe(90.00);
});

it('respects fixed discount type', function () {
    $service = app(QuantityDiscountService::class);
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 100.00,
    ]);

    CategoryQuantityDiscount::create([
        'category_id' => $category->id,
        'min_quantity' => 5,
        'discount_type' => 'fixed',
        'discount_value' => 15.50,
    ]);

    QuantityDiscountService::clearCache();
    $product = $product->fresh();
    expect($service->calculatePrice($product, 10))->toBe(84.50);
});

it('walks up the category tree for discounts', function () {
    $service = app(QuantityDiscountService::class);
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    $product = Product::factory()->create([
        'category_id' => $child->id,
        'price' => 100.00,
    ]);

    CategoryQuantityDiscount::create([
        'category_id' => $parent->id,
        'min_quantity' => 10,
        'discount_type' => 'percent',
        'discount_value' => 20,
    ]);

    // Child has no discount, should use parent
    QuantityDiscountService::clearCache();
    $product = $product->fresh();
    expect($service->calculatePrice($product, 10))->toBe(80.00);
});

it('queries only discounts for the category tree in one query', function () {
    $root = Category::factory()->create();
    $parent = Category::factory()->create(['parent_id' => $root->id]);
    $child = Category::factory()->create(['parent_id' => $parent->id]);
    $unrelatedCategory = Category::factory()->create();

    CategoryQuantityDiscount::create([
        'category_id' => $root->id,
        'min_quantity' => 5,
        'discount_type' => 'percent',
        'discount_value' => 10,
    ]);
    CategoryQuantityDiscount::create([
        'category_id' => $unrelatedCategory->id,
        'min_quantity' => 5,
        'discount_type' => 'percent',
        'discount_value' => 50,
    ]);

    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = strtolower($query->sql);
    });

    $discount = app(QuantityDiscountService::class)->getDiscountForCategoryTree($child->id, 10);

    $discountQueries = array_values(array_filter(
        $queries,
        fn (string $query): bool => str_contains($query, 'category_quantity_discounts'),
    ));

    expect($discount?->category_id)->toBe($root->id)
        ->and($discountQueries)->toHaveCount(1)
        ->and($discountQueries[0])->toContain('category_id')
        ->and($discountQueries[0])->toContain('min_quantity');
});
