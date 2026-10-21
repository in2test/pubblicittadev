<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $category = Category::factory()->create(['name' => 'T-Shirts', 'slug' => 't-shirts']);

    Product::factory()->create([
        'name' => 'Basic-T LS',
        'sku' => 'BASIC-T-LS',
        'description' => 'Long sleeve shirt',
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    Product::factory()->create([
        'name' => 'Basic-T',
        'sku' => 'BASIC-T',
        'description' => 'Crew neck shirt',
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    Product::factory()->create([
        'name' => 'Red Hoodie',
        'sku' => 'HOODIE-RED',
        'description' => 'Warm hoodie',
        'category_id' => $category->id,
        'is_active' => true,
    ]);
});

it('matches every word of a multi word search term', function () {
    $results = app(ProductSearchService::class)->search(
        'BASIC T L',
        Product::query()->active(),
    );

    expect($results->pluck('name')->all())->toBe(['Basic-T LS']);
});

it('returns the same products for the header search and the catalog search', function () {
    $term = 'BASIC T L';

    $headerResults = app(ProductSearchService::class)->search(
        $term,
        Product::query()->active()->with(['category'])->orderBy('name', 'asc'),
    );

    $catalogResults = app(ProductSearchService::class)->search(
        $term,
        Product::query()->where('is_active', '=', true, 'and'),
    );

    expect($headerResults->pluck('id')->sort()->values()->all())
        ->toBe($catalogResults->pluck('id')->sort()->values()->all());
});

it('finds products by every word of the term in the header search box', function () {
    Livewire::test('navigation')
        ->set('searchQuery', 'BASIC T L')
        ->assertSee('Basic-T LS')
        ->assertDontSee('Red Hoodie');
});

it('finds products by every word of the term in the catalog page', function () {
    Livewire::test('catalog')
        ->set('search', 'BASIC T L')
        ->assertSee('Basic-T LS')
        ->assertDontSee('Red Hoodie');
});

it('matches the catalog results when searching from the header', function () {
    $term = 'BASIC T';

    $header = Livewire::test('navigation')->set('searchQuery', $term);

    $catalog = Livewire::test('catalog')->set('search', $term);

    $header->assertSee('Basic-T LS');

    $catalog->assertSee('Basic-T LS');
});

it('searches by sku', function () {
    $results = app(ProductSearchService::class)->search(
        'HOODIE-RED',
        Product::query()->active(),
    );

    expect($results->pluck('name')->all())->toBe(['Red Hoodie']);
});

it('searches by category name', function () {
    $results = app(ProductSearchService::class)->search(
        'T-Shirts',
        Product::query()->active(),
    );

    expect($results)->toHaveCount(3);
});

it('ignores inactive products in the header search', function () {
    Product::factory()->create([
        'name' => 'Hidden Basic-T',
        'sku' => 'HIDDEN-BASIC',
        'category_id' => Category::first()->id,
        'is_active' => false,
    ]);

    $results = app(ProductSearchService::class)->search(
        'Hidden',
        Product::query()->active(),
    );

    expect($results)->toHaveCount(0);
});

it('returns no results for a blank search term', function () {
    $results = app(ProductSearchService::class)->search(
        '   ',
        Product::query()->active(),
    );

    expect($results)->toHaveCount(3);
});
