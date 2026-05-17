<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\VariationOption;
use App\Models\VariationType;
use App\Services\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the cart page successfully with dynamic product variations', function () {
    $category = Category::factory()->create(['slug' => 'apparel']);
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 50,
        'slug' => 'test-product',
    ]);

    $colorType = VariationType::factory()->create(['name' => 'Color', 'presentation_type' => 'color_swatch']);
    $sizeType = VariationType::factory()->create(['name' => 'Size', 'presentation_type' => 'select']);

    $product->variationTypes()->attach([
        $colorType->id => ['sort_order' => 1, 'has_images' => true],
        $sizeType->id => ['sort_order' => 2, 'has_images' => false],
    ]);

    $color = VariationOption::factory()->create(['variation_type_id' => $colorType->id, 'name' => 'Red', 'value' => '#FF0000']);
    $size = VariationOption::factory()->create(['variation_type_id' => $sizeType->id, 'name' => 'Large', 'value' => 'L']);

    $sku = ProductSku::factory()->create([
        'product_id' => $product->id,
        'sku' => 'TEST-VAR',
        'is_available' => true,
    ]);

    $sku->options()->attach([$color->id, $size->id]);

    $cart = app(CartManager::class);
    $cart->add([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_slug' => $product->slug,
        'selected_options' => [
            $colorType->id => $color->id,
        ],
        'options_summary' => [
            'Color' => 'Red',
        ],
        'quantities' => [
            $sku->id => 10,
        ],
        'quantity' => 10,
        'price' => 50,
    ]);

    $response = $this->get(route('cart'));

    $response->assertStatus(200);
    $response->assertSee('Red');
    $response->assertSee('10');
});
