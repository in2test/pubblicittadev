<?php

declare(strict_types=1);

use App\Enums\ProductClass;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it calculates area correctly for minimum area', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::AreaBased,
        'price' => 10.00,
        'min_area' => 1.5,
    ]);

    // 1m x 1m = 1sqm, but min_area is 1.5. So 1.5 * 10 = 15.00
    $price = $product->calculateTotalPrice(totalQuantity: 1, width: 1000.0, height: 1000.0);
    expect($price)->toBe(15.00);
});

test('it calculates area correctly above minimum area', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::AreaBased,
        'price' => 10.00,
        'min_area' => 1.0,
    ]);

    // 2m x 2m = 4sqm. 4 * 10 = 40.00
    $price = $product->calculateTotalPrice(totalQuantity: 1, width: 2000.0, height: 2000.0);
    expect($price)->toBe(40.00);
});

test('it gets correct starting unit price for fixed pricing model', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::ItemBased,
        'price' => 50.00,
        'offer_price' => null,
    ]);

    expect($product->getStartingUnitPrice())->toBe(50.00);

    $product->update(['offer_price' => 45.00]);
    expect($product->getStartingUnitPrice())->toBe(45.00);
});

test('it gets correct starting unit price for quantity pricing model', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
        'price' => 50.00,
    ]);

    $product->pricingTiers()->create([
        'min_quantity' => 10,
        'price_per_unit' => 40.00,
    ]);

    $product->pricingTiers()->create([
        'min_quantity' => 100,
        'price_per_unit' => 35.00,
    ]);

    // The starting unit price should be the lowest price from pricing tiers
    expect($product->fresh()->getStartingUnitPrice())->toBe(35.00);
});

test('it gets correct starting unit price for area pricing model', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::AreaBased,
        'price' => 50.00,
    ]);

    $product->pricingTiers()->create([
        'min_quantity' => 5, // e.g. 5 mq
        'price_per_unit' => 45.00, // per mq
    ]);

    $product->pricingTiers()->create([
        'min_quantity' => 50,
        'price_per_unit' => 38.00,
    ]);

    expect($product->fresh()->getStartingUnitPrice())->toBe(38.00);
});

test('it gets minimum order quantity based on pricing model', function () {
    // Fixed pricing model
    $fixedProduct = Product::factory()->create([
        'product_class' => ProductClass::ItemBased,
    ]);
    expect($fixedProduct->getMinimumOrderQuantity())->toBe(1);

    // Area pricing model
    $areaProduct = Product::factory()->create([
        'product_class' => ProductClass::AreaBased,
    ]);
    expect($areaProduct->getMinimumOrderQuantity())->toBe(1);

    // Quantity pricing model without tiers
    $quantityProductNoTiers = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
    ]);
    expect($quantityProductNoTiers->getMinimumOrderQuantity())->toBe(1);

    // Quantity pricing model with tiers
    $quantityProductWithTiers = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
    ]);
    $quantityProductWithTiers->pricingTiers()->create([
        'min_quantity' => 15,
        'price_per_unit' => 10.0,
    ]);

    // We expect it to be 15
    expect($quantityProductWithTiers->getMinimumOrderQuantity())->toBe(15);
});
