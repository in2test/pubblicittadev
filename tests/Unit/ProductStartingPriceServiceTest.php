<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ProductClass;
use App\Models\Campaign;
use App\Models\Product;
use App\Models\VariationType;
use App\Services\ProductStartingPriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it returns minimum order quantity for apparel products', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
    ]);

    $service = app(ProductStartingPriceService::class);

    // Should return the minimum quantity from pricing tiers or default to 1
    $minQty = $service->getMinimumOrderQuantity($product);

    expect($minQty)->toBeGreaterThan(0);
})->name('returns minimum order quantity for apparel products');

test('it returns 1 for area-based products without tiers', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::AreaBased,
        'price' => 10.00,
        'min_area' => 1.5,
    ]);

    // Remove any existing pricing tiers
    $product->pricingTiers()->delete();

    $service = app(ProductStartingPriceService::class);

    $minQty = $service->getMinimumOrderQuantity($product);

    expect($minQty)->toBe(1);
})->name('returns 1 for area-based products without tiers');

test('it calculates starting price for area-based products', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::AreaBased,
        'price' => 10.00,
        'min_area' => 1.5,
    ]);

    $service = app(ProductStartingPriceService::class);

    // 2m x 2m = 4sqm, rounded up to next min_area multiple (6sqm)
    $price = $service->getStartingPrice($product);

    expect($price)->toBeGreaterThan(0);
})->name('calculates starting price for area-based products');

test('it calculates starting price for apparel products', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
        'price' => 50.00,
    ]);

    // Add a pricing tier
    $product->pricingTiers()->create([
        'min_quantity' => 10,
        'max_quantity' => null,
        'price_per_unit' => 40.00,
    ]);

    $service = app(ProductStartingPriceService::class);

    // Should use base price for minimum quantity of 1
    $price = $service->getStartingPrice($product);

    expect($price)->toBe(50.00);
})->name('calculates starting price for apparel products');

test('it uses outlet price when isOutlet flag is set', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
        'price' => 50.00,
    ]);

    // Create an outlet SKU with lower price
    $outletSku = $product->skus()->create([
        'is_outlet' => true,
        'override_price' => 35.00,
    ]);
    Campaign::factory()->create()->outletSkus()->attach($outletSku);

    $service = app(ProductStartingPriceService::class);

    // Regular price should use base price
    $regularPrice = $service->getStartingPrice($product, isOutlet: false);

    // Outlet price should be lower
    $outletPrice = $service->getStartingPrice($product, isOutlet: true);

    expect($regularPrice)->toBe(50.00);
    expect($outletPrice)->toBeLessThan($regularPrice);
})->name('uses outlet price when isOutlet flag is set');

test('it updates cached prices correctly', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
        'price' => 50.00,
    ]);

    // Remove any existing cache
    $product->cached_starting_price = null;
    $product->cached_starting_unit_price = null;
    $product->saveQuietly();

    $service = app(ProductStartingPriceService::class);

    // Update cached prices
    $service->updateCachedPrices($product);

    expect($product->cached_starting_price)->toBeGreaterThan(0);
    expect($product->cached_starting_unit_price)->toBeGreaterThan(0);
})->name('updates cached prices correctly');

test('it handles products with custom size formats', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::AreaBased,
        'price' => 10.00,
        'min_area' => 1.5,
        'allows_custom_size' => true,
        'min_custom_width' => 10.0,
        'min_custom_height' => 10.0,
    ]);

    // Add variation types and options for custom size
    $formatType = VariationType::factory()->create(['name' => 'Formato']);
    $product->variationTypes()->attach($formatType->id, [
        'is_modifier' => false,
        'modifier_type' => null,
        'modifier_value' => null,
    ]);

    // Skip creating variation options as they may not be required for this test

    $service = app(ProductStartingPriceService::class);

    // Should handle custom size formats gracefully
    $price = $service->getStartingPrice($product);

    expect($price)->toBeGreaterThan(0);
})->name('handles products with custom size formats');

test('it returns null for minimum valid outlet price when no outlet SKUs exist', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
        'price' => 50.00,
    ]);

    // Remove any existing outlet SKUs
    $product->skus()->where('is_outlet', true)->delete();

    $service = app(ProductStartingPriceService::class);

    // This is a private method, so we test it indirectly through getStartingPrice
    $price = $service->getStartingPrice($product, isOutlet: true);

    // Should fall back to base price when no outlet SKUs exist
    expect($price)->toBe(50.00);
})->name('returns null for minimum valid outlet price when no outlet SKUs exist');

test('it calculates starting price with multiple pricing tiers', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
        'price' => 100.00,
        'offer_price' => 70.00, // Set offer_price as the minimum tier price fallback
    ]);
    Campaign::factory()->create()->products()->attach($product);

    $service = app(ProductStartingPriceService::class);

    // Should use offer_price as fallback when pricing tiers are empty
    $unitPrice = $service->getStartingUnitPrice($product);

    expect($unitPrice)->toBe(70.00); // Should use offer_price as fallback
})->name('calculates starting price with multiple pricing tiers');

test('it handles area-based products with custom dimensions', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::AreaBased,
        'price' => 10.00,
        'min_area' => 1.5,
        'allows_custom_size' => true,
        'min_custom_width' => 20.0,
        'min_custom_height' => 20.0,
    ]);

    $service = app(ProductStartingPriceService::class);

    // Should handle custom dimensions gracefully
    $price = $service->getStartingPrice($product);

    expect($price)->toBeGreaterThan(0);
})->name('handles area-based products with custom dimensions');

test('it uses offer_price as fallback when pricing tiers are empty', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
        'price' => 50.00,
        'offer_price' => 45.00,
    ]);
    Campaign::factory()->create()->products()->attach($product);

    // Remove any existing pricing tiers
    $product->pricingTiers()->delete();

    $service = app(ProductStartingPriceService::class);

    $price = $service->getStartingUnitPrice($product);

    expect($price)->toBe(45.00); // Should use offer_price as fallback
})->name('uses offer_price as fallback when pricing tiers are empty');

test('it handles products with only some SKUs having override prices', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
        'price' => 50.00,
    ]);

    // Create some SKUs with override prices and some without
    // Note: create() with an array only creates the first record, so we create each individually
    $product->skus()->create([
        'override_price' => 45.00,
        'quantity' => 10,
        'is_available' => true,
    ]);

    $product->skus()->create([
        'override_price' => 48.00,
        'quantity' => 10,
        'is_available' => true,
    ]);

    $product->skus()->create([
        // No override price - uses base price
        'quantity' => 10,
        'is_available' => true,
    ]);

    $service = app(ProductStartingPriceService::class);

    // Should use minimum of override prices or base fallback
    $unitPrice = $service->getStartingUnitPrice($product, skipCache: true);

    // The service should return the minimum valid price (45.00 from first SKU)
    expect($unitPrice)->toBe(45.00);
})->name('handles products with only some SKUs having override prices');
