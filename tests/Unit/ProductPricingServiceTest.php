<?php

declare(strict_types=1);

use App\Enums\ProductClass;
use App\Models\Category;
use App\Models\CategoryQuantityDiscount;
use App\Models\PricingTier;
use App\Models\Product;
use App\Models\ProductSku;
use App\Services\ProductPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it resolves price from pricing tier', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
        'price' => 50.00,
    ]);

    $product->pricingTiers()->create([
        'min_quantity' => 10,
        'max_quantity' => null,
        'price_per_unit' => 40.00,
    ]);

    $service = app(ProductPricingService::class);

    // Below tier threshold - should use base price
    $price = $service->getPriceForQuantity($product, 5);
    expect($price)->toBe(50.00);

    // At or above tier threshold - should use tier price
    $price = $service->getPriceForQuantity($product, 10);
    expect($price)->toBe(40.00);
})->name('resolves price from pricing tier');

test('it falls back to category discount when no tier matches', function () {
    // Create a product with a parent category that has quantity discounts
    $category = Category::factory()->create(['name' => 'Category A']);
    $parentCategory = Category::factory()->create([
        'name' => 'Parent Category',
        'parent_id' => null,
    ]);

    // Create child category with quantity discount
    $childCategory = Category::factory()->create([
        'name' => 'Child Category',
        'parent_id' => $parentCategory->id,
    ]);

    CategoryQuantityDiscount::create([
        'category_id' => $childCategory->id,
        'min_quantity' => 10,
        'max_quantity' => null,
        'discount_type' => 'percent',
        'discount_value' => 20,
    ]);

    $product = Product::factory()->create([
        'category_id' => $childCategory->id,
        'price' => 100.00,
    ]);

    // Remove pricing tiers to force fallback
    $product->pricingTiers()->delete();

    $service = app(ProductPricingService::class);

    // Below threshold - should use base price
    $price = $service->getPriceForQuantity($product, 5);
    expect($price)->toBe(100.00);

    // At or above threshold - should apply category discount
    $price = $service->getPriceForQuantity($product, 10);
    expect($price)->toBe(80.00); // 100 * (1 - 20%)
})->name('falls back to category discount when no tier matches');

test('it uses product override price when set', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
        'price' => 50.00,
        'offer_price' => 45.00,
    ]);

    $service = app(ProductPricingService::class);

    // Offer price should take precedence over tiers
    $price = $service->getPriceForQuantity($product, 10);
    expect($price)->toBe(45.00);
})->name('uses product override price when set');

test('it handles product with no pricing tiers or category', function () {
    // Create a standalone product (no category)
    $product = Product::factory()->create([
        'name' => 'Standalone Product',
        'slug' => 'standalone-product',
        'description' => 'A product without a category',
        'sku' => 'PRD-STANDALONE',
        'price' => 50.00,
        'product_class' => ProductClass::ItemBased,
        'category_id' => null,
        'is_featured' => false,
    ]);

    $service = app(ProductPricingService::class);

    // Should return base price when no tiers exist
    $price = $service->getPriceForQuantity($product, 10);
    expect($price)->toBe(50.00);
})->name('handles product with no pricing tiers or category');

test('it handles invalid quantity outside all tier ranges', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
        'price' => 50.00,
    ]);

    // Create a tier that only applies to quantities 10-20
    $product->pricingTiers()->create([
        'min_quantity' => 10,
        'max_quantity' => 20,
        'price_per_unit' => 40.00,
    ]);

    $service = app(ProductPricingService::class);

    // Quantity below tier range - should use base price
    $price = $service->getPriceForQuantity($product, 5);
    expect($price)->toBe(50.00);

    // Quantity within tier range - should use tier price
    $price = $service->getPriceForQuantity($product, 15);
    expect($price)->toBe(40.00);

    // Quantity above tier range - should use base price (no higher tier)
    $price = $service->getPriceForQuantity($product, 25);
    expect($price)->toBe(50.00);
})->name('handles invalid quantity outside all tier ranges');

test('it handles category without quantity discounts', function () {
    $category = Category::factory()->create(['name' => 'Category B']);

    $product = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 75.00,
    ]);

    // Remove pricing tiers
    $product->pricingTiers()->delete();

    // Category has no quantity discounts
    CategoryQuantityDiscount::query()
        ->where('category_id', $category->id)
        ->delete();

    $service = app(ProductPricingService::class);

    $price = $service->getPriceForQuantity($product, 10);
    expect($price)->toBe(75.00);
})->name('handles category without quantity discounts');

test('it resolves tier price for specific SKU', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
        'price' => 50.00,
    ]);

    $sku1 = ProductSku::factory()->create([
        'product_id' => $product->id,
        'sku' => 'SKU-RED',
    ]);

    $sku2 = ProductSku::factory()->create([
        'product_id' => $product->id,
        'sku' => 'SKU-BLUE',
    ]);

    // Create tier only for SKU1
    PricingTier::create([
        'product_id' => $product->id,
        'product_sku_id' => $sku1->id,
        'min_quantity' => 5,
        'max_quantity' => null,
        'price_per_unit' => 45.00,
    ]);

    $service = app(ProductPricingService::class);

    // SKU1 with quantity >= 5 should use tier price
    $price = $service->getPriceForQuantity($product, 10, $sku1);
    expect($price)->toBe(45.00);

    // SKU2 with no tier should use base price
    $price = $service->getPriceForQuantity($product, 10, $sku2);
    expect($price)->toBe(50.00);
})->name('resolves tier price for specific SKU');

test('it uses lowest tier price when multiple tiers match', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
        'price' => 50.00,
    ]);

    // Create multiple overlapping tiers
    $product->pricingTiers()->create([
        'min_quantity' => 10,
        'max_quantity' => 20,
        'price_per_unit' => 45.00,
    ]);

    $product->pricingTiers()->create([
        'min_quantity' => 15,
        'max_quantity' => null,
        'price_per_unit' => 40.00,
    ]);

    $service = app(ProductPricingService::class);

    // At quantity 18, both tiers match, should use lowest price (40)
    $price = $service->getPriceForQuantity($product, 18);
    expect($price)->toBe(40.00);
})->name('uses lowest tier price when multiple tiers match');

test('it handles custom size products with starting price', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
        'price' => 50.00,
        'allows_custom_size' => true,
    ]);

    // Create a tier as starting price reference
    $product->pricingTiers()->create([
        'min_quantity' => 1,
        'max_quantity' => null,
        'price_per_unit' => 48.00,
    ]);

    $service = app(ProductPricingService::class);

    // Custom size products should use first tier as starting price
    $price = $service->getPriceForQuantity($product, 1);
    expect($price)->toBe(48.00);
})->name('handles custom size products with starting price');
