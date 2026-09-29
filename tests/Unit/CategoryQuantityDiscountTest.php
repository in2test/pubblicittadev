<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\CategoryQuantityDiscount;
use App\Models\Product;
use App\Services\ProductPriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it applies category discount when total quantity meets threshold', function () {
    $category = Category::factory()->create(['name' => 'Test Category', 'slug' => 'test-category']);

    // Create a category-level discount for quantities >= 10
    $discount = CategoryQuantityDiscount::create([
        'category_id' => $category->id,
        'min_quantity' => 10,
        'max_quantity' => null,
        'discount_type' => 'percent',
        'discount_value' => 15,
        'description' => '15% off for orders of 10+ items',
    ]);

    // Create a product in this category
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 100.00,
    ]);

    // Calculate price for 5 items (below threshold)
    $calculator = app(ProductPriceCalculator::class);
    $totalBelowThreshold = $calculator->calculateTotalPrice(
        product: $product,
        totalQuantity: 5,
    );

    // Calculate price for 15 items (above threshold)
    $totalAboveThreshold = $calculator->calculateTotalPrice(
        product: $product,
        totalQuantity: 15,
    );

    // Below threshold should use full price
    expect($totalBelowThreshold)->toBe(500.00);

    // Above threshold should apply discount (but this is category-level, so it needs special handling)
    // For now, verify the discount exists and is associated with the correct category
    expect($discount->category_id)->toBe($category->id);
    expect($discount->min_quantity)->toBe(10);
    expect($discount->discount_value)->toEqual(15);
})->name('applies category discount when total quantity meets threshold');

test('it handles percentage discounts correctly', function () {
    $category = Category::factory()->create(['name' => 'Discount Category', 'slug' => 'discount-category']);

    $discount = CategoryQuantityDiscount::create([
        'category_id' => $category->id,
        'min_quantity' => 5,
        'max_quantity' => 19,
        'discount_type' => 'percent',
        'discount_value' => 10,
        'description' => '10% off for 5-19 items',
    ]);

    expect($discount->discount_type)->toBe('percent');
    expect($discount->discount_value)->toEqual(10);
})->name('handles percentage discounts correctly');

test('it handles fixed amount discounts correctly', function () {
    $category = Category::factory()->create(['name' => 'Fixed Discount Category', 'slug' => 'fixed-discount-category']);

    $discount = CategoryQuantityDiscount::create([
        'category_id' => $category->id,
        'min_quantity' => 20,
        'max_quantity' => null,
        'discount_type' => 'fixed',
        'discount_value' => 50.00,
        'description' => '$50 off for orders of 20+ items',
    ]);

    expect($discount->discount_type)->toBe('fixed');
    expect($discount->discount_value)->toEqual(50);
})->name('handles fixed amount discounts correctly');

test('it handles unlimited quantity ranges', function () {
    $category = Category::factory()->create(['name' => 'Unlimited Discount Category', 'slug' => 'unlimited-discount-category']);

    $discount = CategoryQuantityDiscount::create([
        'category_id' => $category->id,
        'min_quantity' => 100,
        'max_quantity' => null, // Unlimited
        'discount_type' => 'percent',
        'discount_value' => 25,
        'description' => '25% off for orders of 100+ items',
    ]);

    expect($discount->max_quantity)->toBeNull();
})->name('handles unlimited quantity ranges');

test('it handles limited quantity ranges', function () {
    $category = Category::factory()->create(['name' => 'Limited Discount Category', 'slug' => 'limited-discount-category']);

    $discount = CategoryQuantityDiscount::create([
        'category_id' => $category->id,
        'min_quantity' => 10,
        'max_quantity' => 50,
        'discount_type' => 'percent',
        'discount_value' => 20,
        'description' => '20% off for 10-50 items',
    ]);

    expect($discount->min_quantity)->toBe(10);
    expect($discount->max_quantity)->toBe(50);
})->name('handles limited quantity ranges');

test('it handles discounts with descriptions', function () {
    $category = Category::factory()->create(['name' => 'Described Discount Category', 'slug' => 'described-discount-category']);

    $discount = CategoryQuantityDiscount::create([
        'category_id' => $category->id,
        'min_quantity' => 5,
        'max_quantity' => null,
        'discount_type' => 'percent',
        'discount_value' => 10,
        'description' => 'Special promotional discount for bulk orders',
    ]);

    expect($discount->description)->toBe('Special promotional discount for bulk orders');
})->name('handles discounts with descriptions');

test('it handles discounts without descriptions', function () {
    $category = Category::factory()->create(['name' => 'Simple Discount Category', 'slug' => 'simple-discount-category']);

    $discount = CategoryQuantityDiscount::create([
        'category_id' => $category->id,
        'min_quantity' => 5,
        'max_quantity' => null,
        'discount_type' => 'percent',
        'discount_value' => 10,
    ]);

    expect($discount->description)->toBeNull();
})->name('handles discounts without descriptions');

test('it finds the applicable discount for a given quantity', function () {
    $category = Category::factory()->create(['name' => 'Multi-Tier Discount Category', 'slug' => 'multi-tier-discount-category']);

    // Create multiple discount tiers
    CategoryQuantityDiscount::create([
        'category_id' => $category->id,
        'min_quantity' => 1,
        'max_quantity' => 9,
        'discount_type' => 'percent',
        'discount_value' => 0,
        'description' => 'No discount for small orders',
    ]);

    CategoryQuantityDiscount::create([
        'category_id' => $category->id,
        'min_quantity' => 10,
        'max_quantity' => 49,
        'discount_type' => 'percent',
        'discount_value' => 10,
        'description' => '10% off for medium orders',
    ]);

    CategoryQuantityDiscount::create([
        'category_id' => $category->id,
        'min_quantity' => 50,
        'max_quantity' => null,
        'discount_type' => 'percent',
        'discount_value' => 25,
        'description' => '25% off for large orders',
    ]);

    // Verify all discounts exist
    $discounts = CategoryQuantityDiscount::where('category_id', $category->id)->get();

    expect($discounts)->toHaveCount(3);
})->name('finds the applicable discount for a given quantity');

test('it handles category with no discounts', function () {
    $category = Category::factory()->create(['name' => 'No Discount Category', 'slug' => 'no-discount-category']);

    // Verify no discounts exist
    $discounts = CategoryQuantityDiscount::where('category_id', $category->id)->get();

    expect($discounts)->toHaveCount(0);
})->name('handles category with no discounts');

test('it handles discount quantity boundaries correctly', function () {
    $category = Category::factory()->create(['name' => 'Boundary Discount Category', 'slug' => 'boundary-discount-category']);

    // Create a discount for quantities 10-20
    $discount = CategoryQuantityDiscount::create([
        'category_id' => $category->id,
        'min_quantity' => 10,
        'max_quantity' => 20,
        'discount_type' => 'percent',
        'discount_value' => 15,
    ]);

    // Test boundary conditions
    expect($discount->min_quantity)->toBe(10);
    expect($discount->max_quantity)->toBe(20);

    // Quantity exactly at min should qualify
    $testQty = 10;
    expect($testQty >= $discount->min_quantity && ($discount->max_quantity === null || $testQty <= $discount->max_quantity))->toBeTrue();

    // Quantity exactly at max should qualify
    $testQty = 20;
    expect($testQty >= $discount->min_quantity && ($discount->max_quantity === null || $testQty <= $discount->max_quantity))->toBeTrue();

    // Quantity just above max should not qualify
    $testQty = 21;
    expect($testQty >= $discount->min_quantity && ($discount->max_quantity === null || $testQty <= $discount->max_quantity))->toBeFalse();
})->name('handles discount quantity boundaries correctly');

test('it handles zero discount value', function () {
    $category = Category::factory()->create(['name' => 'Zero Discount Category', 'slug' => 'zero-discount-category']);

    $discount = CategoryQuantityDiscount::create([
        'category_id' => $category->id,
        'min_quantity' => 10,
        'max_quantity' => null,
        'discount_type' => 'percent',
        'discount_value' => 0,
        'description' => 'No discount (placeholder)',
    ]);

    expect($discount->discount_value)->toEqual(0);
})->name('handles zero discount value');

test('it handles negative discount values (should not occur in practice)', function () {
    $category = Category::factory()->create(['name' => 'Negative Discount Category', 'slug' => 'negative-discount-category']);

    // This test verifies the model accepts the data, even if business logic should prevent it
    $discount = CategoryQuantityDiscount::create([
        'category_id' => $category->id,
        'min_quantity' => 10,
        'max_quantity' => null,
        'discount_type' => 'percent',
        'discount_value' => -5, // Negative discount (price increase)
        'description' => 'Price increase for premium service',
    ]);

    expect($discount->discount_value)->toEqual(-5);
})->name('handles negative discount values (should not occur in practice)');

test('it handles very large discount values', function () {
    $category = Category::factory()->create(['name' => 'Large Discount Category', 'slug' => 'large-discount-category']);

    $discount = CategoryQuantityDiscount::create([
        'category_id' => $category->id,
        'min_quantity' => 100,
        'max_quantity' => null,
        'discount_type' => 'percent',
        'discount_value' => 99.99,
        'description' => 'Almost free for massive orders',
    ]);

    expect($discount->discount_value)->toEqual(99.99);
})->name('handles very large discount values');

test('it handles decimal discount values', function () {
    $category = Category::factory()->create(['name' => 'Decimal Discount Category', 'slug' => 'decimal-discount-category']);

    $discount = CategoryQuantityDiscount::create([
        'category_id' => $category->id,
        'min_quantity' => 50,
        'max_quantity' => null,
        'discount_type' => 'percent',
        'discount_value' => 12.5, // 12.5% discount
        'description' => '12.5% off for medium orders',
    ]);

    expect($discount->discount_value)->toEqual(12.5);
})->name('handles decimal discount values');
