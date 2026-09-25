<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutletPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_outlet_page_renders_only_products_with_outlet_skus(): void
    {
        $category = Category::create(['name' => 'Abbigliamento', 'slug' => 'abbigliamento']);

        $outletProduct = Product::create([
            'name' => 'Basic T Outlet',
            'slug' => 'basic-t-outlet',
            'price' => 15,
            'category_id' => $category->id,
            'type' => 'newwave',
            'pricing_model' => 'fixed',
            'is_active' => true,
        ]);

        ProductSku::create([
            'product_id' => $outletProduct->id,
            'sku' => 'BASIC-T-WHITE-S',
            'is_available' => true,
            'is_outlet' => true,
            'override_price' => 9.90,
        ]);

        $regularProduct = Product::create([
            'name' => 'Regular Hoodie',
            'slug' => 'regular-hoodie',
            'price' => 35,
            'category_id' => $category->id,
            'type' => 'newwave',
            'pricing_model' => 'fixed',
            'is_active' => true,
        ]);

        ProductSku::create([
            'product_id' => $regularProduct->id,
            'sku' => 'HOODIE-BLACK-M',
            'is_available' => true,
            'is_outlet' => false,
            'override_price' => null,
        ]);

        $response = $this->get(route('outlet'));
        $response->assertOk();
        $response->assertSee('Basic T Outlet');
        $response->assertDontSee('Regular Hoodie');
    }

    public function test_outlet_applied_to_specific_exposed_variant_only(): void
    {
        $category = Category::create(['name' => 'T-Shirt', 'slug' => 't-shirt']);
        $product = Product::create([
            'name' => 'Basic-T',
            'slug' => 'basic-t',
            'price' => 20,
            'category_id' => $category->id,
            'type' => 'newwave',
            'pricing_model' => 'fixed',
            'is_active' => true,
        ]);

        $colorType = VariationType::create([
            'name' => 'Colore',
            'presentation_type' => 'color_swatch',
            'expose_in_url' => true,
        ]);

        $sizeType = VariationType::create([
            'name' => 'Taglia',
            'presentation_type' => 'radio',
            'expose_in_url' => false,
        ]);

        $colorWhite = VariationOption::create(['variation_type_id' => $colorType->id, 'name' => 'Bianco', 'value' => 'white']);
        $colorYellow = VariationOption::create(['variation_type_id' => $colorType->id, 'name' => 'Giallo', 'value' => 'yellow']);

        $sizeS = VariationOption::create(['variation_type_id' => $sizeType->id, 'name' => 'S', 'value' => 's']);
        $sizeM = VariationOption::create(['variation_type_id' => $sizeType->id, 'name' => 'M', 'value' => 'm']);

        // Sku Bianco S, Bianco M -> Outlet @ 8.00
        $skuWhiteS = ProductSku::create(['product_id' => $product->id, 'sku' => 'BT-W-S', 'is_outlet' => true, 'override_price' => 8.00]);
        $skuWhiteS->options()->attach([$colorWhite->id, $sizeS->id]);

        $skuWhiteM = ProductSku::create(['product_id' => $product->id, 'sku' => 'BT-W-M', 'is_outlet' => true, 'override_price' => 8.00]);
        $skuWhiteM->options()->attach([$colorWhite->id, $sizeM->id]);

        // Sku Giallo S, Giallo M -> Standard (NOT outlet, price 20)
        $skuYellowS = ProductSku::create(['product_id' => $product->id, 'sku' => 'BT-Y-S', 'is_outlet' => false, 'override_price' => null]);
        $skuYellowS->options()->attach([$colorYellow->id, $sizeS->id]);

        $skuYellowM = ProductSku::create(['product_id' => $product->id, 'sku' => 'BT-Y-M', 'is_outlet' => false, 'override_price' => null]);
        $skuYellowM->options()->attach([$colorYellow->id, $sizeM->id]);

        // Assert White SKUs are outlet with 8.00
        $this->assertTrue($skuWhiteS->is_outlet);
        $this->assertEquals(8.00, (float) $skuWhiteS->override_price);
        $this->assertTrue($skuWhiteM->is_outlet);
        $this->assertEquals(8.00, (float) $skuWhiteM->override_price);

        // Assert Yellow SKUs are NOT outlet
        $this->assertFalse($skuYellowS->is_outlet);
        $this->assertNull($skuYellowS->override_price);
        $this->assertFalse($skuYellowM->is_outlet);
        $this->assertNull($skuYellowM->override_price);

        // Product starting unit price for outlet reflects 8.00
        $this->assertEquals(8.00, $product->getStartingUnitPrice(false, true));
    }
}
