<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSku;
use App\Services\ProductPriceCalculator;
use App\Services\ProductPricingService;
use App\Services\ProductVariantResolver;
use App\Services\QuantityDiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutletPricingTest extends TestCase
{
    use RefreshDatabase;

    private ProductPriceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ProductPriceCalculator(
            new ProductPricingService(new QuantityDiscountService),
            new ProductVariantResolver
        );
    }

    public function test_outlet_sku_uses_override_price(): void
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $product = Product::create([
            'name' => 'Outlet Product',
            'slug' => 'outlet-product',
            'price' => 100,
            'category_id' => $category->id,
            'type' => 'standard',
            'pricing_model' => 'fixed',
            'is_active' => true,
        ]);

        $sku = ProductSku::create([
            'product_id' => $product->id,
            'sku' => 'OUTLET-SKU',
            'is_available' => true,
            'is_outlet' => true,
            'override_price' => 50.0,
        ]);

        // Price for 1 unit
        $total = $this->calculator->calculateTotalPrice(
            $product,
            1,
            [$sku->id => 1]
        );

        $this->assertEquals(50.0, $total);
    }

    public function test_standard_sku_uses_product_price(): void
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $product = Product::create([
            'name' => 'Standard Product',
            'slug' => 'standard-product',
            'price' => 100,
            'category_id' => $category->id,
            'type' => 'standard',
            'pricing_model' => 'fixed',
            'is_active' => true,
        ]);

        $sku = ProductSku::create([
            'product_id' => $product->id,
            'sku' => 'STD-SKU',
            'is_available' => true,
            'is_outlet' => false,
            'override_price' => null,
        ]);

        $total = $this->calculator->calculateTotalPrice(
            $product,
            1,
            [$sku->id => 1]
        );

        $this->assertEquals(100.0, $total);
    }
}
