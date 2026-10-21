<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SyncLevel;
use App\Models\Brand;
use App\Models\Product;
use App\Services\BrandResolver;
use App\Services\NwgApiClient;
use App\Services\ProductAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(RefreshDatabase::class);

it('resolves the brand relation through the brand_id column', function () {
    $product = Product::factory()->create(['type' => Product::TYPE_NEWWAVE]);
    $brand = Brand::factory()->create(['name' => 'Clique']);

    $product->update(['brand_id' => $brand->id]);

    expect($product->fresh())
        ->brand_relation->toBeInstanceOf(Brand::class)
        ->and($product->fresh()->brand_relation->name)->toBe('Clique')
        ->and($product->fresh()->brand)->toBe('Clique');
});

it('creates the brand and assigns it to the product when syncing metadata', function () {
    $product = Product::factory()->create([
        'sku' => 'BRAND-SYNC-SKU',
        'type' => Product::TYPE_NEWWAVE,
    ]);

    $mockClient = Mockery::mock(NwgApiClient::class);
    $mockClient->shouldReceive('getProductMetadata')
        ->once()
        ->with('BRAND-SYNC-SKU')
        ->andReturn([
            'productName' => 'Basic-T',
            'productBrand' => 'Clique',
        ]);
    $this->app->instance(NwgApiClient::class, $mockClient);

    app(ProductAvailabilityService::class)->syncByLevel($product, SyncLevel::Meta);

    $product->refresh();

    expect($product->brand_id)->not->toBeNull()
        ->and($product->brand_relation->name)->toBe('Clique')
        ->and(Brand::query()->where('name', 'Clique')->count())->toBe(1);
});

it('reuses the existing brand record when the same brand is synced again', function () {
    $brand = Brand::factory()->create(['name' => 'Clique']);

    $product = Product::factory()->create([
        'sku' => 'BRAND-REUSE-SKU',
        'type' => Product::TYPE_NEWWAVE,
    ]);

    $mockClient = Mockery::mock(NwgApiClient::class);
    $mockClient->shouldReceive('getProductMetadata')
        ->once()
        ->with('BRAND-REUSE-SKU')
        ->andReturn([
            'productName' => 'Basic-T',
            'productBrand' => 'Clique',
        ]);
    $this->app->instance(NwgApiClient::class, $mockClient);

    app(ProductAvailabilityService::class)->syncByLevel($product, SyncLevel::Meta);

    expect($product->refresh()->brand_id)->toBe($brand->id)
        ->and(Brand::query()->count())->toBe(1);
});

it('keeps the current brand when the API payload has no brand', function () {
    $brand = Brand::factory()->create(['name' => 'Clique']);

    $product = Product::factory()->create([
        'sku' => 'BRAND-MISSING-SKU',
        'type' => Product::TYPE_NEWWAVE,
        'brand_id' => $brand->id,
    ]);

    $mockClient = Mockery::mock(NwgApiClient::class);
    $mockClient->shouldReceive('getProductMetadata')
        ->once()
        ->with('BRAND-MISSING-SKU')
        ->andReturn([
            'productName' => 'Basic-T',
        ]);
    $this->app->instance(NwgApiClient::class, $mockClient);

    app(ProductAvailabilityService::class)->syncByLevel($product, SyncLevel::Meta);

    expect($product->refresh()->brand_id)->toBe($brand->id);
});

it('normalises the brand name coming from the API', function () {
    $product = Product::factory()->create([
        'sku' => 'BRAND-NORMALISE-SKU',
        'type' => Product::TYPE_NEWWAVE,
    ]);

    $mockClient = Mockery::mock(NwgApiClient::class);
    $mockClient->shouldReceive('getProductMetadata')
        ->once()
        ->with('BRAND-NORMALISE-SKU')
        ->andReturn([
            'productName' => 'Basic-T',
            'productBrand' => '  Clique   Pro  ',
        ]);
    $this->app->instance(NwgApiClient::class, $mockClient);

    app(ProductAvailabilityService::class)->syncByLevel($product, SyncLevel::Meta);

    expect($product->refresh()->brand_relation->name)->toBe('Clique Pro');
});

it('returns no brand when the API payload has a blank brand', function () {
    $resolver = app(BrandResolver::class);

    expect($resolver->resolve(null))->toBeNull()
        ->and($resolver->resolve('   '))->toBeNull()
        ->and(Brand::query()->count())->toBe(0);
});

it('assigns the brand when running a full sync', function () {
    $product = Product::factory()->create([
        'sku' => 'BRAND-FULL-SYNC-SKU',
        'type' => Product::TYPE_NEWWAVE,
    ]);

    $mockClient = Mockery::mock(NwgApiClient::class);
    $mockClient->shouldReceive('getFullProductData')
        ->once()
        ->with('BRAND-FULL-SYNC-SKU')
        ->andReturn([
            'productName' => 'Basic-T',
            'productBrand' => 'Clique',
            'retailPrice' => ['price' => 6.90],
            'variations' => [],
        ]);
    $this->app->instance(NwgApiClient::class, $mockClient);

    app(ProductAvailabilityService::class)->syncByLevel($product, SyncLevel::Full);

    expect($product->refresh()->brand_relation->name)->toBe('Clique');
});
