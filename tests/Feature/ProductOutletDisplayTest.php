<?php

use App\Models\Campaign;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;

uses(RefreshDatabase::class);

test('product info shows outlet starting price without an explicit sku selection', function () {
    $product = Product::factory()->create([
        'price' => 20,
        'pricing_model' => 'fixed',
    ]);

    $sku = ProductSku::create([
        'product_id' => $product->id,
        'sku' => 'OUTLET-SKU',
        'is_outlet' => true,
        'override_price' => 8,
    ]);
    Campaign::factory()->create()->outletSkus()->attach($sku);

    ProductSku::create([
        'product_id' => $product->id,
        'sku' => 'REGULAR-SKU',
        'is_outlet' => false,
    ]);

    $productView = Blade::render(
        '<x-product.info :product="$product" :currentBasePrice="$price" />',
        ['product' => $product, 'price' => 20],
    );

    expect($productView)->toContain('€8,00')
        ->toContain('Outlet');

    $regularSkuView = Blade::render(
        '<x-product.info :product="$product" :currentBasePrice="$price" :hasExplicitSkuSelection="true" />',
        ['product' => $product, 'price' => 20],
    );

    expect($regularSkuView)->toContain('€20,00')
        ->not->toContain('Outlet');

    $outletSkuView = Blade::render(
        '<x-product.info :product="$product" :currentBasePrice="$price" :isOutletSelected="true" :hasExplicitSkuSelection="true" />',
        ['product' => $product, 'price' => 8],
    );

    expect($outletSkuView)->toContain('€8,00')
        ->toContain('Outlet');
});
