<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryQuantityDiscount;
use App\Models\Product;
use App\Services\QuantityDiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
    expect($service->calculatePrice($product, 10))->toBe(80.00);
});
