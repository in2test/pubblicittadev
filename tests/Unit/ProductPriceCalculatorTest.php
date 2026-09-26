<?php

declare(strict_types=1);

use App\Enums\ProductClass;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ProductVariationOption;
use App\Models\ProductVariationType;
use App\Models\VariationOption;
use App\Models\VariationType;
use App\Services\ProductPriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it calculates price for fixed pricing model', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::ItemBased,
        'price' => 50.00,
    ]);

    $calculator = app(ProductPriceCalculator::class);

    $total = $calculator->calculateTotalPrice(
        product: $product,
        totalQuantity: 10,
    );

    expect($total)->toBe(500.00);
})->name('calculates price for fixed pricing model');

test('it calculates price for quantity pricing model with tiers', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
        'price' => 50.00,
    ]);

    $product->pricingTiers()->create([
        'min_quantity' => 10,
        'max_quantity' => null,
        'price_per_unit' => 40.00,
    ]);

    $calculator = app(ProductPriceCalculator::class);

    // Below tier threshold - should use base price
    $total = $calculator->calculateTotalPrice(
        product: $product,
        totalQuantity: 5,
    );

    expect($total)->toBe(250.00);

    // At or above tier threshold - should use tier price
    $total = $calculator->calculateTotalPrice(
        product: $product,
        totalQuantity: 10,
    );

    expect($total)->toBe(400.00);
})->name('calculates price for quantity pricing model with tiers');

test('it calculates price for area-based pricing', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::AreaBased,
        'price' => 10.00,
        'min_area' => 1.5,
    ]);

    // Debug: check what price is being used
    $calculator = app(ProductPriceCalculator::class);

    // 2m x 2m = 4sqm
    $total = $calculator->calculateTotalPrice(
        product: $product,
        totalQuantity: 1,
        width: 2000.0,
        height: 2000.0,
    );

    // Expected: 10 * 4.5 = 45 (area rounded up to next min_area multiple)
    expect($total)->toBe(45.00);
})->name('calculates price for area-based pricing');

test('it calculates price for area-based pricing with no tiers', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::AreaBased,
        'price' => 10.00,
        'min_area' => 1.5,
    ]);

    // Ensure no pricing tiers exist
    $product->pricingTiers()->delete();

    $calculator = app(ProductPriceCalculator::class);

    // 2m x 2m = 4sqm
    $total = $calculator->calculateTotalPrice(
        product: $product,
        totalQuantity: 1,
        width: 2000.0,
        height: 2000.0,
    );

    // Expected: 10 * 4.5 = 45 (area rounded up to next min_area multiple)
    expect($total)->toBe(45.00);
})->name('calculates price for area-based pricing with no tiers');

test('it applies percentage modifier correctly', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::ItemBased,
        'price' => 100.00,
    ]);

    // Add a 10% discount modifier (negative value = discount)
    $variationType = VariationType::factory()->create(['name' => 'Opzione']);
    $pivot = ProductVariationType::create([
        'product_id' => $product->id,
        'variation_type_id' => $variationType->id,
        'is_modifier' => true,  // Set directly on the model, not in pivot array
        'modifier_type' => 'percentage',
        'modifier_value' => 10,
    ]);

    $option = VariationOption::factory()->create();
    ProductVariationOption::create([
        'variation_option_id' => $option->id,
        'product_variation_type_id' => $pivot->id,
        'modifier_type' => 'percentage',
        'price_modifier' => -10, // Negative = discount
    ]);

    $calculator = app(ProductPriceCalculator::class);

    // Eager-load options so the calculator can access them from memory
    $product->load(['variationTypes', 'productVariationTypes', 'productVariationTypes.options']);

    // selectedOptions should be an array keyed by variation_type_id with arrays of option IDs
    $total = $calculator->calculateTotalPrice(
        product: $product,
        totalQuantity: 10,
        selectedOptions: [
            $variationType->id => [$option->id],
        ],
    );

    // 100 * 10 = 1000, minus 10% = 900
    expect($total)->toBe(900.00);
})->name('applies percentage modifier correctly');

test('it handles negative quantity gracefully', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::ItemBased,
        'price' => 50.00,
    ]);

    $calculator = app(ProductPriceCalculator::class);

    // Should handle gracefully and return 0 or throw appropriate error
    $total = $calculator->calculateTotalPrice(
        product: $product,
        totalQuantity: -5,
    );

    expect($total)->toBe(0.00);
})->name('rejects invalid quantity (negative)');

test('it uses SKU override price when set', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::ItemBased,
        'price' => 50.00,
    ]);

    $sku = ProductSku::factory()->create([
        'product_id' => $product->id,
        'override_price' => 45.00,
    ]);

    $calculator = app(ProductPriceCalculator::class);

    $total = $calculator->calculateTotalPrice(
        product: $product,
        totalQuantity: 10,
    );

    // Should use override price instead of base price
    expect($total)->toBe(450.00);
})->name('uses SKU override price when set');

test('it applies flat modifier correctly', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::ItemBased,
        'price' => 100.00,
    ]);

    // Add a 20 flat discount modifier (negative value = discount)

    $variationType = VariationType::factory()->create(['name' => 'Opzione']);
    $pivot = ProductVariationType::create([
        'product_id' => $product->id,
        'variation_type_id' => $variationType->id,
        'is_modifier' => true,  // Set directly on the model, not in pivot array
        'modifier_type' => 'flat',
        'modifier_value' => 20,
    ]);

    $option = VariationOption::factory()->create();
    ProductVariationOption::create([
        'variation_option_id' => $option->id,
        'product_variation_type_id' => $pivot->id,
        'modifier_type' => ModifierType::Flat,  // Use enum instead of string
        'price_modifier' => -20.0, // Negative = discount (use float)
    ]);

    $calculator = app(ProductPriceCalculator::class);

    // Eager-load options so the calculator can access them from memory
    $product->load(['variationTypes', 'productVariationTypes', 'productVariationTypes.options']);

    // selectedOptions should be an array keyed by variation_type_id with arrays of option IDs
    $total = $calculator->calculateTotalPrice(
        product: $product,
        totalQuantity: 10,
        selectedOptions: [
            $variationType->id => [$option->id],
        ],
    );

    // 100 * 10 = 1000, minus 20 flat = 980
    expect($total)->toBe(980.00);
})->name('applies flat modifier correctly');

test('it returns zero for zero quantity', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::ItemBased,
        'price' => 50.00,
    ]);

    $calculator = app(ProductPriceCalculator::class);

    $total = $calculator->calculateTotalPrice(
        product: $product,
        totalQuantity: 0,
    );

    expect($total)->toBe(0.00);
})->name('returns zero for zero quantity');

test('it handles product with no pricing tiers', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
        'price' => 50.00,
    ]);

    // Remove any existing tiers
    $product->pricingTiers()->delete();

    $calculator = app(ProductPriceCalculator::class);

    $total = $calculator->calculateTotalPrice(
        product: $product,
        totalQuantity: 10,
    );

    expect($total)->toBe(500.00);
})->name('handles product with no pricing tiers');

test('it handles area calculation edge cases', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::AreaBased,
        'price' => 10.00,
        'min_area' => 1.5,
    ]);

    $calculator = app(ProductPriceCalculator::class);

    // Missing width - should return 0
    $total = $calculator->calculateTotalPrice(
        product: $product,
        totalQuantity: 1,
        height: 2000.0,
    );

    expect($total)->toBe(0.00);

    // Missing height - should return 0
    $total = $calculator->calculateTotalPrice(
        product: $product,
        totalQuantity: 1,
        width: 2000.0,
    );

    expect($total)->toBe(0.00);
})->name('handles area calculation edge cases');

test('it calculates multi-SKU cart correctly', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::ItemBased,
        'price' => 50.00,
    ]);

    $calculator = app(ProductPriceCalculator::class);

    $total = $calculator->calculateTotalPrice(
        product: $product,
        totalQuantity: 15,
        skuQuantities: [
            1 => 10, // 10 items at base price
            2 => 5,  // 5 items at base price
        ],
    );

    expect($total)->toBe(750.00);
})->name('calculates multi-SKU cart correctly');
