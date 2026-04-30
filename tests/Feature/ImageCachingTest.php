<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use App\Models\Product;
use App\Jobs\CacheProductImagesJob;

uses(RefreshDatabase::class);

it('dispatches CacheProductImagesJob via CLI for a product', function () {
    // Arrange
    Bus::fake();
    $product = Product::create([
        'sku' => 'TEST-IMG-001',
        'name' => 'Test Product for Images',
        'description' => 'Description',
        'price' => 9.99,
        'type' => 'newwave',
        'sync_progress' => 0,
        'is_active' => true,
    ]);

    // Act
    Artisan::call('images:cache', ['product_id' => $product->id]);

    // Assert
    Bus::assertDispatched(function (CacheProductImagesJob $job) use ($product) {
        return $job->product->id === $product->id;
    });
});
