<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::factory()->create(['name' => 'T-Shirts', 'slug' => 't-shirts']);
    $this->clique = Brand::factory()->create(['name' => 'Clique']);
    $this->projob = Brand::factory()->create(['name' => 'ProJob']);
});

it('lists the brands that have products in the current catalog', function () {
    Product::factory()->create([
        'name' => 'Clique Shirt',
        'category_id' => $this->category->id,
        'brand_id' => $this->clique->id,
        'is_active' => true,
    ]);

    Livewire::test('catalog')
        ->assertSee('Brand')
        ->assertSee('Clique')
        ->assertDontSee('ProJob');
});

it('filters the products by the selected brand', function () {
    Product::factory()->create([
        'name' => 'Clique Shirt',
        'category_id' => $this->category->id,
        'brand_id' => $this->clique->id,
        'is_active' => true,
    ]);

    Product::factory()->create([
        'name' => 'ProJob Shirt',
        'category_id' => $this->category->id,
        'brand_id' => $this->projob->id,
        'is_active' => true,
    ]);

    Livewire::test('catalog')
        ->set('selectedBrands', [$this->clique->id])
        ->assertSee('Clique Shirt')
        ->assertDontSee('ProJob Shirt');
});

it('keeps the other brands selectable while a brand is selected', function () {
    Product::factory()->create([
        'name' => 'Clique Shirt',
        'category_id' => $this->category->id,
        'brand_id' => $this->clique->id,
        'is_active' => true,
    ]);

    Product::factory()->create([
        'name' => 'ProJob Shirt',
        'category_id' => $this->category->id,
        'brand_id' => $this->projob->id,
        'is_active' => true,
    ]);

    Livewire::test('catalog')
        ->set('selectedBrands', [$this->clique->id])
        ->assertSee('ProJob')
        ->call('toggleBrand', $this->projob->id)
        ->assertSee('Clique Shirt')
        ->assertSee('ProJob Shirt');
});

it('clears the brand filter when the filters are reset', function () {
    Product::factory()->create([
        'name' => 'Clique Shirt',
        'category_id' => $this->category->id,
        'brand_id' => $this->clique->id,
        'is_active' => true,
    ]);

    Product::factory()->create([
        'name' => 'ProJob Shirt',
        'category_id' => $this->category->id,
        'brand_id' => $this->projob->id,
        'is_active' => true,
    ]);

    Livewire::test('catalog')
        ->set('selectedBrands', [$this->clique->id])
        ->assertDontSee('ProJob Shirt')
        ->call('resetFilters')
        ->assertSee('ProJob Shirt');
});

it('finds products by brand name through the text search', function () {
    Product::factory()->create([
        'name' => 'Basic-T',
        'category_id' => $this->category->id,
        'brand_id' => $this->clique->id,
        'is_active' => true,
    ]);

    Livewire::test('catalog')
        ->set('search', 'Clique')
        ->assertSee('Basic-T');
});

it('renders the brand filter on the search page', function () {
    Product::factory()->create([
        'name' => 'Basic-T',
        'category_id' => $this->category->id,
        'brand_id' => $this->clique->id,
        'is_active' => true,
    ]);

    $this->get(route('search', ['q' => 'Basic']))
        ->assertOk()
        ->assertSee('Clique');
});

it('does not list brands without matching products', function () {
    Product::factory()->create([
        'name' => 'Brandless Shirt',
        'category_id' => $this->category->id,
        'brand_id' => null,
        'is_active' => true,
    ]);

    Livewire::test('catalog')
        ->assertDontSee('Clique')
        ->assertDontSee('ProJob');
});
