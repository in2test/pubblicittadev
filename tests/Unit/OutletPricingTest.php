<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\CategoryQuantityDiscount;
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

    /**
     * Bug 1 regression: category quantity discount must be applied on top
     * of the outlet override_price, not just on the product's base price.
     */
    public function test_outlet_sku_applies_category_quantity_discount_on_override_price(): void
    {
        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel']);

        CategoryQuantityDiscount::create([
            'category_id' => $category->id,
            'min_quantity' => 10,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 10, // 10% off for 10+
        ]);

        $product = Product::create([
            'name' => 'Outlet Apparel',
            'slug' => 'outlet-apparel',
            'price' => 100,
            'category_id' => $category->id,
            'type' => 'newwave',
            'pricing_model' => 'fixed',
            'is_active' => true,
        ]);

        $sku = ProductSku::create([
            'product_id' => $product->id,
            'sku' => 'OUTLET-APPAREL-SKU',
            'is_available' => true,
            'is_outlet' => true,
            'override_price' => 50.0, // outlet price
        ]);

        // 10 units of outlet SKU @ 50€ with 10% category discount = 50 * 0.9 * 10 = 450
        $total = $this->calculator->calculateTotalPrice(
            $product,
            10,
            [$sku->id => 10]
        );

        $this->assertEquals(450.0, $total);
    }

    /**
     * Bug 1 regression: category quantity discount must be applied on top
     * of the offer_price, not skipped entirely.
     */
    public function test_offer_price_applies_category_quantity_discount(): void
    {
        $category = Category::create(['name' => 'Promozioni', 'slug' => 'promozioni']);

        CategoryQuantityDiscount::create([
            'category_id' => $category->id,
            'min_quantity' => 10,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 10, // 10% off for 10+
        ]);

        $product = Product::create([
            'name' => 'Offer Product',
            'slug' => 'offer-product',
            'price' => 100,
            'offer_price' => 60, // product-level offer price
            'category_id' => $category->id,
            'type' => 'standard',
            'pricing_model' => 'fixed',
            'is_active' => true,
        ]);

        $product->load('skus');

        // 10 units @ offer price 60€ with 10% category discount = 60 * 0.9 * 10 = 540
        $total = $this->calculator->calculateTotalPrice(
            $product,
            10,
        );

        $this->assertEquals(540.0, $total);
    }
}
