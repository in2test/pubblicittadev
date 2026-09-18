<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\CategoryQuantityDiscount;
use App\Models\Product;
use App\Models\ProductSku;
use App\Services\ProductPriceCalculator;
use App\Services\QuantityDiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    QuantityDiscountService::clearCache();
});

/**
 * Helper: create a product with a category that has a percent discount at a given threshold.
 * Returns the product and three SKUs (XS, S, M).
 *
 * @return array{product: Product, skuXs: ProductSku, skuS: ProductSku, skuM: ProductSku}
 */
function makeMultiSkuProduct(float $price, int $discountThreshold, float $discountPercent): array
{
    $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel']);

    CategoryQuantityDiscount::create([
        'category_id' => $category->id,
        'min_quantity' => $discountThreshold,
        'max_quantity' => null,
        'discount_type' => 'percent',
        'discount_value' => $discountPercent,
        'description' => "{$discountPercent}% at {$discountThreshold}+",
    ]);

    $product = Product::factory()->create([
        'price' => $price,
        'category_id' => $category->id,
        'type' => 'newwave',
    ]);

    $skuXs = ProductSku::create(['product_id' => $product->id, 'sku' => 'SKU-XS', 'quantity' => 100, 'is_available' => true]);
    $skuS = ProductSku::create(['product_id' => $product->id, 'sku' => 'SKU-S',  'quantity' => 100, 'is_available' => true]);
    $skuM = ProductSku::create(['product_id' => $product->id, 'sku' => 'SKU-M',  'quantity' => 100, 'is_available' => true]);

    return ['product' => $product, 'skuXs' => $skuXs, 'skuS' => $skuS, 'skuM' => $skuM];
}

// ---------------------------------------------------------------------------
// Happy path — discount applied when grand total crosses threshold
// ---------------------------------------------------------------------------

it('applies discount when quantities spread across multiple SKUs reach the threshold', function (): void {
    // 8 XS + 4 S + 5 M = 17 total → ≥10 → 5% discount on €6.90
    ['product' => $product, 'skuXs' => $skuXs, 'skuS' => $skuS, 'skuM' => $skuM] = makeMultiSkuProduct(6.90, 10, 5.0);

    $product->loadMissing(['skus.options', 'pricingTiers', 'variationTypes']);

    $calculator = app(ProductPriceCalculator::class);

    $skuQuantities = [
        $skuXs->id => 8,
        $skuS->id => 4,
        $skuM->id => 5,
    ];
    $totalQuantity = 17;

    $total = $calculator->calculateTotalPrice($product, $totalQuantity, $skuQuantities);

    // Expected: 6.90 * 0.95 = 6.555 → 6.56 per unit × 17 = 111.47 (rounded per unit)
    $expectedUnitPrice = round(6.90 * 0.95, 2);
    $expectedTotal = round($expectedUnitPrice * $totalQuantity, 2);

    expect($total)->toBe($expectedTotal);
});

it('applies the correct discount tier when total hits exactly the threshold', function (): void {
    // Exactly 10 units split across two SKUs → 5% discount
    ['product' => $product, 'skuXs' => $skuXs, 'skuS' => $skuS] = makeMultiSkuProduct(10.0, 10, 5.0);

    $product->loadMissing(['skus.options', 'pricingTiers', 'variationTypes']);

    $calculator = app(ProductPriceCalculator::class);

    $skuQuantities = [$skuXs->id => 6, $skuS->id => 4]; // 10 total
    $total = $calculator->calculateTotalPrice($product, 10, $skuQuantities);

    $expectedUnitPrice = round(10.0 * 0.95, 2);
    expect($total)->toBe(round($expectedUnitPrice * 10, 2));
});

it('applies the highest discount tier when grand total crosses multiple thresholds', function (): void {
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat']);

    CategoryQuantityDiscount::create([
        'category_id' => $category->id, 'min_quantity' => 10, 'max_quantity' => null,
        'discount_type' => 'percent', 'discount_value' => 5, 'description' => '5% at 10+',
    ]);
    CategoryQuantityDiscount::create([
        'category_id' => $category->id, 'min_quantity' => 25, 'max_quantity' => null,
        'discount_type' => 'percent', 'discount_value' => 10, 'description' => '10% at 25+',
    ]);

    $product = Product::factory()->create(['price' => 10.0, 'category_id' => $category->id, 'type' => 'newwave']);
    $skuXs = ProductSku::create(['product_id' => $product->id, 'sku' => 'XS', 'quantity' => 100, 'is_available' => true]);
    $skuS = ProductSku::create(['product_id' => $product->id, 'sku' => 'S',  'quantity' => 100, 'is_available' => true]);

    $product->loadMissing(['skus.options', 'pricingTiers', 'variationTypes']);

    $calculator = app(ProductPriceCalculator::class);
    $skuQuantities = [$skuXs->id => 15, $skuS->id => 15]; // 30 total → 10% tier

    $total = $calculator->calculateTotalPrice($product, 30, $skuQuantities);
    $expectedUnitPrice = round(10.0 * 0.90, 2);
    expect($total)->toBe(round($expectedUnitPrice * 30, 2));
});

it('gives every SKU the same discounted unit price when total qualifies', function (): void {
    // All sizes should receive the same discounted per-unit price, not a mix
    ['product' => $product, 'skuXs' => $skuXs, 'skuS' => $skuS, 'skuM' => $skuM] = makeMultiSkuProduct(100.0, 10, 10.0);

    $product->loadMissing(['skus.options', 'pricingTiers', 'variationTypes']);

    $calculator = app(ProductPriceCalculator::class);

    // 4 of each SKU = 12 total → discount active
    $total = $calculator->calculateTotalPrice($product, 12, [$skuXs->id => 4, $skuS->id => 4, $skuM->id => 4]);
    $expectedUnitPrice = round(100.0 * 0.90, 2); // 90.00
    $expectedTotal = round($expectedUnitPrice * 12, 2);

    expect($total)->toBe($expectedTotal);
});

// ---------------------------------------------------------------------------
// Unhappy path — no discount when grand total is below threshold
// ---------------------------------------------------------------------------

it('does not apply discount when grand total is below the threshold', function (): void {
    // 3 XS + 2 S + 4 M = 9 total → below threshold of 10 → full price
    ['product' => $product, 'skuXs' => $skuXs, 'skuS' => $skuS, 'skuM' => $skuM] = makeMultiSkuProduct(6.90, 10, 5.0);

    $product->loadMissing(['skus.options', 'pricingTiers', 'variationTypes']);

    $calculator = app(ProductPriceCalculator::class);

    $skuQuantities = [$skuXs->id => 3, $skuS->id => 2, $skuM->id => 4]; // 9 total
    $total = $calculator->calculateTotalPrice($product, 9, $skuQuantities);

    expect($total)->toBe(round(6.90 * 9, 2));
});

it('does not apply discount when only one SKU exceeds the threshold but the grand total does not', function (): void {
    // Threshold is 20; XS has 12, S has 1, M has 1 → grand total 14 → no discount
    ['product' => $product, 'skuXs' => $skuXs, 'skuS' => $skuS, 'skuM' => $skuM] = makeMultiSkuProduct(10.0, 20, 15.0);

    $product->loadMissing(['skus.options', 'pricingTiers', 'variationTypes']);

    $calculator = app(ProductPriceCalculator::class);

    $skuQuantities = [$skuXs->id => 12, $skuS->id => 1, $skuM->id => 1]; // 14 total — below 20
    $total = $calculator->calculateTotalPrice($product, 14, $skuQuantities);

    expect($total)->toBe(round(10.0 * 14, 2));
});

it('returns zero for zero total quantity', function (): void {
    ['product' => $product, 'skuXs' => $skuXs] = makeMultiSkuProduct(10.0, 5, 10.0);
    $product->loadMissing(['skus.options', 'pricingTiers', 'variationTypes']);

    $calculator = app(ProductPriceCalculator::class);
    $total = $calculator->calculateTotalPrice($product, 0, [$skuXs->id => 0]);

    expect($total)->toBe(0.0);
});

// ---------------------------------------------------------------------------
// Regression — single-SKU behaviour still works correctly after the fix
// ---------------------------------------------------------------------------

it('still correctly prices a single SKU that reaches the threshold on its own', function (): void {
    ['product' => $product, 'skuXs' => $skuXs] = makeMultiSkuProduct(10.0, 10, 10.0);
    $product->loadMissing(['skus.options', 'pricingTiers', 'variationTypes']);

    $calculator = app(ProductPriceCalculator::class);
    $total = $calculator->calculateTotalPrice($product, 10, [$skuXs->id => 10]);

    $expectedUnitPrice = round(10.0 * 0.90, 2);
    expect($total)->toBe(round($expectedUnitPrice * 10, 2));
});

it('still returns full price for a single SKU below the threshold', function (): void {
    ['product' => $product, 'skuXs' => $skuXs] = makeMultiSkuProduct(10.0, 10, 10.0);
    $product->loadMissing(['skus.options', 'pricingTiers', 'variationTypes']);

    $calculator = app(ProductPriceCalculator::class);
    $total = $calculator->calculateTotalPrice($product, 5, [$skuXs->id => 5]);

    expect($total)->toBe(round(10.0 * 5, 2));
});
