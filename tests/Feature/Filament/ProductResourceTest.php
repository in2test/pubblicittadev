<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can render the product list page', function () {
    Livewire::test(ListProducts::class)
        ->assertSuccessful();
});

it('can list products', function () {
    $products = Product::factory()->count(5)->create();

    Livewire::test(ListProducts::class)
        ->assertCanSeeTableRecords($products);
});

it('can render create product page', function () {
    Livewire::test(CreateProduct::class)
        ->assertSuccessful();
});

it('can create a product', function () {
    $category = Category::factory()->create();
    $newData = Product::factory()->make([
        'category_id' => $category->id,
    ]);

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => $newData->name,
            'slug' => $newData->slug,
            'description' => $newData->description,
            'price' => $newData->price,
            'category_id' => $newData->category_id,
            'is_featured' => $newData->is_featured,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Product::class, [
        'name' => $newData->name,
        'slug' => $newData->slug,
    ]);
});

it('can render edit product page', function () {
    $product = Product::factory()->create();

    Livewire::test(EditProduct::class, [
        'record' => $product->getRouteKey(),
    ])
        ->assertSuccessful();
});

it('can edit a product', function () {
    $product = Product::factory()->create();
    $newData = Product::factory()->make();

    Livewire::test(EditProduct::class, [
        'record' => $product->getRouteKey(),
    ])
        ->fillForm([
            'name' => $newData->name,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->refresh()->name)->toBe($newData->name);
});

it('can delete a product', function () {
    $product = Product::factory()->create();

    Livewire::test(EditProduct::class, [
        'record' => $product->getRouteKey(),
    ])
        ->callAction('delete');

    $this->assertModelMissing($product);
});
