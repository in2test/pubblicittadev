<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\PricingTier;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\VariationOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

final class ProductSkuTest
{
    /** @test */
    public function it_creates_sku_with_valid_options(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);

        // Create variation options for the SKU
        $colorOption = VariationOption::factory()->create(['name' => 'Bianco', 'hex_color' => '#FFFFFF']);
        $sizeOption = VariationOption::factory()->create(['name' => 'L', 'dimension_type' => 'size', 'value' => 'L']);

        $sku = ProductSku::create([
            'product_id' => $product->id,
            'sku' => 'TEST-L-BIANCO',
            'quantity' => 100,
            'is_available' => true,
        ]);

        // Associate options with SKU
        $sku->options()->attach([
            $colorOption->id,
            $sizeOption->id,
        ]);

        $this->assertEquals($product->id, $sku->product_id);
        $this->assertEquals('TEST-L-BIANCO', $sku->sku);
        $this->assertEquals(100, $sku->quantity);
        $this->assertTrue($sku->is_available);
    }

    /** @test */
    public function it_sets_quantity_to_negative_when_null(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);

        $sku = ProductSku::create([
            'product_id' => $product->id,
            'sku' => 'TEST-NULL',
            'quantity' => null, // Should be converted to -1
            'is_available' => false,
        ]);

        $this->assertEquals(-1, $sku->quantity);
    }

    /** @test */
    public function it_marks_sku_as_outlet(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);

        $sku = ProductSku::create([
            'product_id' => $product->id,
            'sku' => 'TEST-OUTLET',
            'quantity' => 50,
            'is_available' => true,
            'is_outlet' => true,
        ]);

        $this->assertTrue($sku->isOutlet());
    }

    /** @test */
    public function it_sets_outlet_price_correctly(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 29.99,
        ]);

        $sku = ProductSku::create([
            'product_id' => $product->id,
            'sku' => 'TEST-OUTLET-PRICE',
            'quantity' => 50,
            'is_available' => true,
            'override_price' => 24.99,
        ]);

        $this->assertEquals(24.99, $sku->override_price);
    }

    /** @test */
    public function it_allows_outlet_price_higher_than_regular(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 29.99,
        ]);

        // Outlet price can be higher than regular price (special promotion)
        $sku = ProductSku::create([
            'product_id' => $product->id,
            'sku' => 'TEST-HIGHER-OUTLET',
            'quantity' => 50,
            'is_available' => true,
            'override_price' => 34.99, // Higher than base price
        ]);

        $this->assertEquals(34.99, $sku->override_price);
    }

    /** @test */
    public function it_updates_sku_correctly(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);

        $sku = ProductSku::create([
            'product_id' => $product->id,
            'sku' => 'TEST-UPDATE',
            'quantity' => 100,
            'is_available' => true,
        ]);

        $sku->update([
            'quantity' => 200,
            'is_available' => false,
        ]);

        $this->assertEquals(200, $sku->fresh()->quantity);
        $this->assertFalse($sku->fresh()->is_available);
    }

    /** @test */
    public function it_deletes_sku_correctly(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);

        $sku = ProductSku::create([
            'product_id' => $product->id,
            'sku' => 'TEST-DELETE',
            'quantity' => 100,
            'is_available' => true,
        ]);

        $sku->delete();

        $this->assertDatabaseMissing('product_skus', [
            'product_id' => $product->id,
            'sku' => 'TEST-DELETE',
        ]);
    }

    /** @test */
    public function it_queries_skus_for_product(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);

        ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku' => 'SKU-1',
            'quantity' => 50,
            'is_available' => true,
        ]);

        ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku' => 'SKU-2',
            'quantity' => 30,
            'is_available' => false,
        ]);

        $skus = ProductSku::where('product_id', $product->id)->get();

        $this->assertCount(2, $skus);
    }

    /** @test */
    public function it_filters_skus_by_availability(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);

        ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku' => 'SKU-AVAILABLE',
            'quantity' => 50,
            'is_available' => true,
        ]);

        ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku' => 'SKU-UNAVAILABLE',
            'quantity' => 0,
            'is_available' => false,
        ]);

        $availableSkus = ProductSku::where('product_id', $product->id)
            ->whereTrue('is_available')
            ->get();

        $this->assertCount(1, $availableSkus);
    }

    /** @test */
    public function it_handles_multiple_products(): void
    {
        $product1 = Product::factory()->create(['name' => 'Product 1']);
        $product2 = Product::factory()->create(['name' => 'Product 2']);

        ProductSku::factory()->create([
            'product_id' => $product1->id,
            'sku' => 'SKU-PROD1',
            'quantity' => 100,
            'is_available' => true,
        ]);

        ProductSku::factory()->create([
            'product_id' => $product2->id,
            'sku' => 'SKU-PROD2',
            'quantity' => 50,
            'is_available' => true,
        ]);

        $allSkus = ProductSku::all();

        $this->assertCount(2, $allSkus);
    }

    /** @test */
    public function it_orders_skus_by_quantity_descending(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);

        ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku' => 'SKU-LOW',
            'quantity' => 10,
            'is_available' => true,
        ]);

        ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku' => 'SKU-MEDIUM',
            'quantity' => 50,
            'is_available' => true,
        ]);

        ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku' => 'SKU-HIGH',
            'quantity' => 100,
            'is_available' => true,
        ]);

        $skus = ProductSku::where('product_id', $product->id)
            ->orderByDesc('quantity')
            ->get();

        $this->assertEquals('SKU-HIGH', $skus[0]->sku);
        $this->assertEquals('SKU-MEDIUM', $skus[1]->sku);
        $this->assertEquals('SKU-LOW', $skus[2]->sku);
    }

    /** @test */
    public function it_handles_sku_with_no_options(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);

        ProductSku::create([
            'product_id' => $product->id,
            'sku' => 'TEST-NO-OPTIONS',
            'quantity' => 100,
            'is_available' => true,
        ]);

        // SKU exists but has no variation options attached
        $this->assertDatabaseHas('product_skus', [
            'product_id' => $product->id,
            'sku' => 'TEST-NO-OPTIONS',
        ]);
    }

    /** @test */
    public function it_handles_sku_with_multiple_options(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);

        // Create multiple variation options
        VariationOption::factory()->count(3)->create();

        $sku = ProductSku::create([
            'product_id' => $product->id,
            'sku' => 'TEST-MULTI-OPTIONS',
            'quantity' => 100,
            'is_available' => true,
        ]);

        // Attach all options to SKU
        $options = VariationOption::all();
        $sku->options()->attach($options->pluck('id')->toArray());

        $this->assertCount(3, $sku->options);
    }

    /** @test */
    public function it_handles_sku_with_pricing_tiers(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);

        $sku = ProductSku::create([
            'product_id' => $product->id,
            'sku' => 'TEST-TIERS',
            'quantity' => 100,
            'is_available' => true,
        ]);

        // Create pricing tiers for this SKU
        PricingTier::factory()->create([
            'product_id' => $product->id,
            'product_sku_id' => $sku->id,
            'min_quantity' => 10,
            'max_quantity' => 49,
            'price_per_unit' => 24.99,
        ]);

        PricingTier::factory()->create([
            'product_id' => $product->id,
            'product_sku_id' => $sku->id,
            'min_quantity' => 50,
            'max_quantity' => null,
            'price_per_unit' => 19.99,
        ]);

        $this->assertCount(2, $sku->pricingTiers);
    }

    /** @test */
    public function it_handles_sku_with_no_pricing_tiers(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);

        $sku = ProductSku::create([
            'product_id' => $product->id,
            'sku' => 'TEST-NO-TIERS',
            'quantity' => 100,
            'is_available' => true,
        ]);

        $this->assertCount(0, $sku->pricingTiers);
    }

    /** @test */
    public function it_handles_outlet_sku_correctly(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'is_outlet' => true,
            'outlet_price' => 24.99,
        ]);

        // Outlet SKU should inherit is_outlet from product
        $sku = ProductSku::create([
            'product_id' => $product->id,
            'sku' => 'TEST-OUTLET-SKU',
            'quantity' => 50,
            'is_available' => true,
            // is_outlet not explicitly set, should inherit from product
        ]);

        $this->assertTrue($sku->isOutlet());
    }

    /** @test */
    public function it_handles_non_outlet_sku_correctly(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'is_outlet' => false,
        ]);

        $sku = ProductSku::create([
            'product_id' => $product->id,
            'sku' => 'TEST-NON-OUTLET-SKU',
            'quantity' => 50,
            'is_available' => true,
        ]);

        $this->assertFalse($sku->isOutlet());
    }
}
