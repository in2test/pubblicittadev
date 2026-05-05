<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Size;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('can render the catalog component', function () {
    Livewire::test('catalog')
        ->assertStatus(200);
});

it('can filter products by search query', function () {
    $category = Category::factory()->create(['name' => 'T-Shirts', 'slug' => 't-shirts']);

    $product1 = Product::factory()->create([
        'name' => 'Blue T-Shirt',
        'category_id' => $category->id,
        'is_active' => true,
    ]);
    $product2 = Product::factory()->create([
        'name' => 'Red Hoodie',
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    Livewire::test('catalog', ['categorySlug' => 't-shirts'])
        ->set('search', 'Blue')
        ->assertSee('Blue T-Shirt')
        ->assertDontSee('Red Hoodie');
});

it('can filter products by category', function () {
    $parent = Category::factory()->create(['name' => 'Apparel', 'slug' => 'apparel']);
    $child = Category::factory()->create(['name' => 'Shirts', 'slug' => 'shirts', 'parent_id' => $parent->id]);

    $product1 = Product::factory()->create(['name' => 'Shirt A', 'category_id' => $child->id, 'is_active' => true]);
    $product2 = Product::factory()->create(['name' => 'Pants B', 'category_id' => $parent->id, 'is_active' => true]);

    Livewire::test('catalog', ['categorySlug' => 'shirts'])
        ->assertSee('Shirt A')
        ->assertDontSee('Pants B');
});

it('can filter products by color', function () {
    $category = Category::factory()->create(['name' => 'Shirts', 'slug' => 'shirts']);
    $colorBlue = Color::factory()->create(['color_name' => 'Blue']);
    $colorRed = Color::factory()->create(['color_name' => 'Red']);

    $product1 = Product::factory()->create(['name' => 'Blue Shirt', 'category_id' => $category->id, 'is_active' => true]);
    $product2 = Product::factory()->create(['name' => 'Red Shirt', 'category_id' => $category->id, 'is_active' => true]);

    $size = Size::factory()->create();

    ProductVariation::factory()->create([
        'product_id' => $product1->id,
        'color_id' => $colorBlue->id,
        'size_id' => $size->id,
        'is_available' => true,
        'quantity' => 10,
    ]);

    ProductVariation::factory()->create([
        'product_id' => $product2->id,
        'color_id' => $colorRed->id,
        'size_id' => $size->id,
        'is_available' => true,
        'quantity' => 10,
    ]);

    Livewire::test('catalog', ['categorySlug' => 'shirts'])
        ->set('selectedColors', [$colorBlue->id])
        ->assertSee('Blue Shirt')
        ->assertDontSee('Red Shirt');
});

it('can sort products by price ascending', function () {
    $category = Category::factory()->create(['name' => 'Shirts', 'slug' => 'shirts']);

    Product::factory()->create(['name' => 'Cheap Shirt', 'price' => 10.00, 'category_id' => $category->id, 'is_active' => true]);
    Product::factory()->create(['name' => 'Expensive Shirt', 'price' => 100.00, 'category_id' => $category->id, 'is_active' => true]);

    Livewire::test('catalog', ['categorySlug' => 'shirts'])
        ->set('sort', 'price_asc')
        ->assertSeeInOrder(['Cheap Shirt', 'Expensive Shirt']);
});

it('can sort products by price descending', function () {
    $category = Category::factory()->create(['name' => 'Shirts', 'slug' => 'shirts']);

    Product::factory()->create(['name' => 'Cheap Shirt', 'price' => 10.00, 'category_id' => $category->id, 'is_active' => true]);
    Product::factory()->create(['name' => 'Expensive Shirt', 'price' => 100.00, 'category_id' => $category->id, 'is_active' => true]);

    Livewire::test('catalog', ['categorySlug' => 'shirts'])
        ->set('sort', 'price_desc')
        ->assertSeeInOrder(['Expensive Shirt', 'Cheap Shirt']);
});
