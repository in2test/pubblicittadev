<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Services\NwgApiClient;
use App\Services\ProductSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(RefreshDatabase::class);

it('synchronizes product data correctly from API', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'sku' => 'TEST-SKU',
        'type' => 'newwave',
        'category_id' => $category->id,
    ]);

    $apiData = [
        'productName' => 'New API Name',
        'retailPrice' => ['price' => 99.99],
        'productCatalogText' => 'Description from API',
        'variations' => [
            [
                'itemColorCode' => '10',
                'itemWebColor' => 'Ocean Blue',
                'skus' => [
                    [
                        'sku' => 'TEST-SKU-10-M',
                        'availability' => 20,
                        'skuSize' => ['size' => 'M', 'webtext' => 'Medium'],
                        'active' => true,
                    ],
                ],
            ],
        ],
    ];

    $mockClient = Mockery::mock(NwgApiClient::class);
    $mockClient->shouldReceive('getFullProductData')
        ->once()
        ->with('TEST-SKU')
        ->andReturn($apiData);

    // Swap the singleton in the container
    $this->app->instance(NwgApiClient::class, $mockClient);

    app(ProductSynchronizer::class)->syncProduct($product);

    $product->refresh();

    expect($product->name)->toBe('New API Name');
    expect((float) $product->price)->toBe(99.99);
    expect($product->description)->toBe('Description from API');
    expect($product->variations)->toHaveCount(1);

    $variation = $product->variations->first();
    expect($variation->sku)->toBe('TEST-SKU-10-M');
    expect($variation->quantity)->toBe(10); // Halving logic: floor(20 / 2)
    expect($variation->color->color_code)->toBe('10');
    expect($variation->size->size_code)->toBe('M');
});

it('does not update price if override_price is set', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'sku' => 'OVERRIDE-SKU',
        'type' => 'newwave',
        'price' => 150.00,
        'override_price' => true,
        'category_id' => $category->id,
    ]);

    $apiData = [
        'productName' => 'API Name',
        'retailPrice' => ['price' => 99.99],
        'variations' => [],
    ];

    $mockClient = Mockery::mock(NwgApiClient::class);
    $mockClient->shouldReceive('getFullProductData')->andReturn($apiData);
    $this->app->instance(NwgApiClient::class, $mockClient);

    app(ProductSynchronizer::class)->syncProduct($product);

    $product->refresh();
    expect((float) $product->price)->toBe(150.00);
});
