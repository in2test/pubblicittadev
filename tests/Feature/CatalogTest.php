<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\VariationOption;
use App\Models\VariationType;
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

it('can filter products by variation option', function () {
    $category = Category::factory()->create(['name' => 'Shirts', 'slug' => 'shirts']);

    $colorType = VariationType::factory()->create(['name' => 'Color', 'presentation_type' => 'color_swatch']);

    $colorBlue = VariationOption::factory()->create(['variation_type_id' => $colorType->id, 'name' => 'Blue']);
    $colorRed = VariationOption::factory()->create(['variation_type_id' => $colorType->id, 'name' => 'Red']);

    $product1 = Product::factory()->create(['name' => 'Blue Shirt', 'category_id' => $category->id, 'is_active' => true]);
    $product2 = Product::factory()->create(['name' => 'Red Shirt', 'category_id' => $category->id, 'is_active' => true]);

    $product1->variationTypes()->attach($colorType->id);
    $product2->variationTypes()->attach($colorType->id);

    \App\Models\ProductVariationOption::create([
        'product_variation_type_id' => $product1->productVariationTypes()->first()->id,
        'variation_option_id' => $colorBlue->id,
    ]);

    \App\Models\ProductVariationOption::create([
        'product_variation_type_id' => $product2->productVariationTypes()->first()->id,
        'variation_option_id' => $colorRed->id,
    ]);

    $sku1 = ProductSku::factory()->create([
        'product_id' => $product1->id,
        'is_available' => true,
        'quantity' => 10,
    ]);
    $sku1->options()->attach($colorBlue->id);

    $sku2 = ProductSku::factory()->create([
        'product_id' => $product2->id,
        'is_available' => true,
        'quantity' => 10,
    ]);
    $sku2->options()->attach($colorRed->id);

    Livewire::test('catalog', ['categorySlug' => 'shirts'])
        ->set('selectedOptions', [$colorBlue->id])
        ->assertSee('Blue Shirt')
        ->assertDontSee('Red Shirt');
});

it('enforces AND logic across different variation types and OR logic within the same type', function () {
    $category = Category::factory()->create(['name' => 'Shirts', 'slug' => 'shirts']);

    $colorType = VariationType::factory()->create(['name' => 'Color']);
    $colorBlue = VariationOption::factory()->create(['variation_type_id' => $colorType->id, 'name' => 'Blue']);
    $colorRed = VariationOption::factory()->create(['variation_type_id' => $colorType->id, 'name' => 'Red']);

    $positionType = VariationType::factory()->create(['name' => 'Print Position']);
    $posFront = VariationOption::factory()->create(['variation_type_id' => $positionType->id, 'name' => 'Front']);
    $posBack = VariationOption::factory()->create(['variation_type_id' => $positionType->id, 'name' => 'Back']);

    $productBlueFront = Product::factory()->create(['name' => 'Blue Front', 'category_id' => $category->id, 'is_active' => true]);
    $productBlueBack = Product::factory()->create(['name' => 'Blue Back', 'category_id' => $category->id, 'is_active' => true]);
    $productRedFront = Product::factory()->create(['name' => 'Red Front', 'category_id' => $category->id, 'is_active' => true]);

    // Attach types
    $productBlueFront->variationTypes()->attach([$colorType->id, $positionType->id]);
    $productBlueBack->variationTypes()->attach([$colorType->id, $positionType->id]);
    $productRedFront->variationTypes()->attach([$colorType->id, $positionType->id]);

    // Attach options
    \App\Models\ProductVariationOption::create(['product_variation_type_id' => $productBlueFront->productVariationTypes()->where('variation_type_id', $colorType->id)->first()->id, 'variation_option_id' => $colorBlue->id]);
    \App\Models\ProductVariationOption::create(['product_variation_type_id' => $productBlueFront->productVariationTypes()->where('variation_type_id', $positionType->id)->first()->id, 'variation_option_id' => $posFront->id]);

    \App\Models\ProductVariationOption::create(['product_variation_type_id' => $productBlueBack->productVariationTypes()->where('variation_type_id', $colorType->id)->first()->id, 'variation_option_id' => $colorBlue->id]);
    \App\Models\ProductVariationOption::create(['product_variation_type_id' => $productBlueBack->productVariationTypes()->where('variation_type_id', $positionType->id)->first()->id, 'variation_option_id' => $posBack->id]);

    \App\Models\ProductVariationOption::create(['product_variation_type_id' => $productRedFront->productVariationTypes()->where('variation_type_id', $colorType->id)->first()->id, 'variation_option_id' => $colorRed->id]);
    \App\Models\ProductVariationOption::create(['product_variation_type_id' => $productRedFront->productVariationTypes()->where('variation_type_id', $positionType->id)->first()->id, 'variation_option_id' => $posFront->id]);

    // Scenario 1: Select only 'Blue'. Should see Blue Front and Blue Back, but not Red Front.
    Livewire::test('catalog', ['categorySlug' => 'shirts'])
        ->set('selectedOptions', [$colorBlue->id])
        ->assertSee('Blue Front')
        ->assertSee('Blue Back')
        ->assertDontSee('Red Front');

    // Scenario 2: Select 'Blue' AND 'Front'. Should ONLY see Blue Front.
    Livewire::test('catalog', ['categorySlug' => 'shirts'])
        ->set('selectedOptions', [$colorBlue->id, $posFront->id])
        ->assertSee('Blue Front')
        ->assertDontSee('Blue Back')
        ->assertDontSee('Red Front');

    // Scenario 3: Select 'Blue' OR 'Red' AND 'Front'. Should see Blue Front and Red Front.
    Livewire::test('catalog', ['categorySlug' => 'shirts'])
        ->set('selectedOptions', [$colorBlue->id, $colorRed->id, $posFront->id])
        ->assertSee('Blue Front')
        ->assertSee('Red Front')
        ->assertDontSee('Blue Back');
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

it('expands categories down to the selected subcategory in category-tree component', function () {
    $parent = Category::factory()->create(['name' => 'Apparel', 'slug' => 'apparel']);
    $child = Category::factory()->create(['name' => 'Shirts', 'slug' => 'shirts', 'parent_id' => $parent->id]);
    $grandchild = Category::factory()->create(['name' => 'T-Shirts', 'slug' => 't-shirts', 'parent_id' => $child->id]);

    $test = Livewire::test('catalog', ['categorySlug' => 't-shirts']);

    expect($test->instance()->category->id)->toBe($grandchild->id);
});
