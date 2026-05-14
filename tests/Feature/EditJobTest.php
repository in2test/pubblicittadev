<?php

use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Size;
use App\Services\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('can edit an existing job in the cart', function () {
    $category = Category::factory()->create(['slug' => 'apparel']);
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 50,
        'slug' => 'test-product',
    ]);

    $color = Color::create(['color_code' => 'RED', 'color_name' => 'Red']);
    $size = Size::create(['size_code' => 'L', 'size_name' => 'Large', 'size' => 'L']);
    ProductVariation::create([
        'product_id' => $product->id,
        'color_id' => $color->id,
        'size_id' => $size->id,
        'sku' => 'TEST-VAR',
        'is_available' => true,
    ]);

    $cart = app(CartManager::class);
    $cart->add([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_slug' => $product->slug,
        'quantity' => 10,
        'color_id' => $color->id,
        'price' => 50,
    ]);

    $jobId = array_key_first($cart->getItems());

    Livewire::test('⚡product', [
        'product' => $product,
        'category' => $category,
        'colorId' => $color->id,
        'jobId' => $jobId,
    ])
        ->set("quantities.{$size->id}", 20)
        ->call('addToCart')
        ->assertRedirect(route('cart'));

    $items = $cart->getItems();
    expect($items[$jobId]['quantity'])->toBe(20);
});
