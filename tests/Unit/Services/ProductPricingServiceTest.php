<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\CategoryQuantityDiscount;
use App\Models\PricingTier;
use App\Models\Product;
use App\Models\ProductSku;
use App\Services\ProductPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

final readonly class ProductPricingServiceTest
{
    private ProductPricingService $service;

    /** @test */
    public function it_returns_offer_price_when_active(): void
    {
        $product = Product::factory()->create([
            'offer_price' => 19.99,
        ]);

        $price = $this->service->getPriceForQuantity($product, 1);

        $this->assertEquals(19.99, $price);
    }

    /** @test */
    public function it_returns_tier_price_when_available(): void
    {
        $product = Product::factory()->create([
            'price' => 29.99,
        ]);

        PricingTier::factory()->create([
            'product_id' => $product->id,
            'min_quantity' => 10,
            'max_quantity' => 49,
            'price_per_unit' => 24.99,
        ]);

        $price = $this->service->getPriceForQuantity($product, 15);

        $this->assertEquals(24.99, $price);
    }

    /** @test */
    public function it_falls_back_to_quantity_discount_when_no_tier_matches(): void
    {
        $product = Product::factory()->create([
            'price' => 29.99,
        ]);

        // Create a tier that doesn't match the quantity
        PricingTier::factory()->create([
            'product_id' => $product->id,
            'min_quantity' => 50,
            'max_quantity' => null,
            'price_per_unit' => 19.99,
        ]);

        // Create category discount
        $category = Product::factory()->create(['name' => 'Test Category']);
        CategoryQuantityDiscount::factory()->create([
            'category_id' => $category->id,
            'min_quantity' => 1,
            'max_quantity' => 9,
            'discount_type' => 'percent',
            'discount_value' => 10,
        ]);

        $price = $this->service->getPriceForQuantity($product, 5);

        // Should apply 10% discount to base price
        $expected = 29.99 * 0.9;
        $this->assertEquals($expected, $price);
    }

    /** @test */
    public function it_returns_null_when_no_tier_matches_and_no_category_discount(): void
    {
        $product = Product::factory()->create([
            'price' => 29.99,
        ]);

        // Create a tier that doesn't match the quantity
        PricingTier::factory()->create([
            'product_id' => $product->id,
            'min_quantity' => 50,
            'max_quantity' => null,
            'price_per_unit' => 19.99,
        ]);

        $tierPrice = $this->service->getTierPrice($product, 5);

        $this->assertNull($tierPrice);
    }

    /** @test */
    public function it_returns_tier_price_for_specific_sku(): void
    {
        $product = Product::factory()->create([
            'price' => 29.99,
        ]);

        $sku = ProductSku::factory()->create([
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        PricingTier::factory()->create([
            'product_id' => $product->id,
            'product_sku_id' => $sku->id,
            'min_quantity' => 5,
            'max_quantity' => 19,
            'price_per_unit' => 27.99,
        ]);

        $price = $this->service->getPriceForQuantity($product, 10, $sku);

        $this->assertEquals(27.99, $price);
    }

    /** @test */
    public function it_falls_back_to_base_price_when_no_sku_tier_matches(): void
    {
        $product = Product::factory()->create([
            'price' => 29.99,
        ]);

        $sku = ProductSku::factory()->create([
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        // Create tier for different SKU
        PricingTier::factory()->create([
            'product_id' => $product->id,
            'product_sku_id' => null,
            'min_quantity' => 10,
            'max_quantity' => 19,
            'price_per_unit' => 24.99,
        ]);

        $price = $this->service->getPriceForQuantity($product, 10, $sku);

        // Should fall back to base price since no SKU-specific tier matches
        $this->assertEquals(29.99, $price);
    }

    /** @test */
    public function it_returns_first_tier_for_custom_size_products(): void
    {
        $product = Product::factory()->create([
            'price' => 0,
            'allows_custom_size' => true,
        ]);

        PricingTier::factory()->create([
            'product_id' => $product->id,
            'min_quantity' => 10,
            'max_quantity' => 49,
            'price_per_unit' => 24.99,
        ]);

        PricingTier::factory()->create([
            'product_id' => $product->id,
            'min_quantity' => 50,
            'max_quantity' => null,
            'price_per_unit' => 19.99,
        ]);

        // Should return first tier (lowest quantity) for custom size products
        $price = $this->service->getPriceForQuantity($product, 25);

        $this->assertEquals(24.99, $price);
    }

    /** @test */
    public function it_returns_null_for_zero_quantity(): void
    {
        $product = Product::factory()->create([
            'price' => 29.99,
        ]);

        PricingTier::factory()->create([
            'product_id' => $product->id,
            'min_quantity' => 10,
            'max_quantity' => 49,
            'price_per_unit' => 24.99,
        ]);

        $tierPrice = $this->service->getTierPrice($product, 0);

        $this->assertNull($tierPrice);
    }

    /** @test */
    public function it_returns_null_for_negative_quantity(): void
    {
        $product = Product::factory()->create([
            'price' => 29.99,
        ]);

        PricingTier::factory()->create([
            'product_id' => $product->id,
            'min_quantity' => 10,
            'max_quantity' => 49,
            'price_per_unit' => 24.99,
        ]);

        $tierPrice = $this->service->getTierPrice($product, -5);

        $this->assertNull($tierPrice);
    }

    /** @test */
    public function it_returns_null_when_product_has_no_tiers(): void
    {
        $product = Product::factory()->create([
            'price' => 29.99,
        ]);

        $tierPrice = $this->service->getTierPrice($product, 10);

        $this->assertNull($tierPrice);
    }

    /** @test */
    public function it_returns_price_for_area_based_product(): void
    {
        $product = Product::factory()->create([
            'price' => 45.00,
            'pricing_model' => 'area',
        ]);

        $price = $this->service->getPriceForAreaBasedProduct($product);

        $this->assertEquals(45.00, $price);
    }

    /** @test */
    public function it_handles_quantity_at_tier_boundary(): void
    {
        $product = Product::factory()->create([
            'price' => 29.99,
        ]);

        PricingTier::factory()->create([
            'product_id' => $product->id,
            'min_quantity' => 10,
            'max_quantity' => 49,
            'price_per_unit' => 24.99,
        ]);

        PricingTier::factory()->create([
            'product_id' => $product->id,
            'min_quantity' => 50,
            'max_quantity' => null,
            'price_per_unit' => 19.99,
        ]);

        // At exactly 50 units, should use the 50+ tier
        $price = $this->service->getPriceForQuantity($product, 50);

        $this->assertEquals(19.99, $price);
    }

    /** @test */
    public function it_returns_null_for_quantity_above_all_tiers(): void
    {
        $product = Product::factory()->create([
            'price' => 29.99,
        ]);

        PricingTier::factory()->create([
            'product_id' => $product->id,
            'min_quantity' => 10,
            'max_quantity' => 49,
            'price_per_unit' => 24.99,
        ]);

        // Quantity of 100 is above the max of all tiers
        $tierPrice = $this->service->getTierPrice($product, 100);

        $this->assertNull($tierPrice);
    }

    /** @test */
    public function it_handles_tiers_with_null_max_quantity(): void
    {
        $product = Product::factory()->create([
            'price' => 29.99,
        ]);

        PricingTier::factory()->create([
            'product_id' => $product->id,
            'min_quantity' => 10,
            'max_quantity' => null, // No upper limit
            'price_per_unit' => 24.99,
        ]);

        $price = $this->service->getPriceForQuantity($product, 100);

        $this->assertEquals(24.99, $price);
    }

    /** @test */
    public function it_returns_min_price_when_multiple_tiers_match(): void
    {
        $product = Product::factory()->create([
            'price' => 29.99,
        ]);

        // Create multiple overlapping tiers
        PricingTier::factory()->create([
            'product_id' => $product->id,
            'min_quantity' => 10,
            'max_quantity' => 49,
            'price_per_unit' => 24.99,
        ]);

        PricingTier::factory()->create([
            'product_id' => $product->id,
            'min_quantity' => 20,
            'max_quantity' => 99,
            'price_per_unit' => 22.99,
        ]);

        // At quantity 25, both tiers match, should return min price
        $price = $this->service->getPriceForQuantity($product, 25);

        $this->assertEquals(22.99, $price);
    }

    /** @test */
    public function it_handles_invalid_discount_type(): void
    {
        $product = Product::factory()->create([
            'price' => 29.99,
        ]);

        // Create tier that doesn't match
        PricingTier::factory()->create([
            'product_id' => $product->id,
            'min_quantity' => 50,
            'max_quantity' => null,
            'price_per_unit' => 19.99,
        ]);

        // Create category discount with invalid type
        $category = Product::factory()->create(['name' => 'Test Category']);
        CategoryQuantityDiscount::factory()->create([
            'category_id' => $category->id,
            'min_quantity' => 1,
            'max_quantity' => 9,
            'discount_type' => 'invalid',
            'discount_value' => 10,
        ]);

        // Should not crash and should fall back to base price
        $price = $this->service->getPriceForQuantity($product, 5);

        $this->assertEquals(29.99, $price);
    }

    /** @test */
    public function it_handles_negative_discount_value(): void
    {
        $product = Product::factory()->create([
            'price' => 29.99,
        ]);

        // Create tier that doesn't match
        PricingTier::factory()->create([
            'product_id' => $product->id,
            'min_quantity' => 50,
            'max_quantity' => null,
            'price_per_unit' => 19.99,
        ]);

        // Create category discount with negative value
        $category = Product::factory()->create(['name' => 'Test Category']);
        CategoryQuantityDiscount::factory()->create([
            'category_id' => $category->id,
            'min_quantity' => 1,
            'max_quantity' => 9,
            'discount_type' => 'percent',
            'discount_value' => -5, // Negative discount (shouldn't happen but test edge case)
        ]);

        // Should not crash
        $price = $this->service->getPriceForQuantity($product, 5);

        // Should apply the negative discount (increase price)
        $expected = 29.99 * 0.95;
        $this->assertEquals($expected, $price);
    }
}
