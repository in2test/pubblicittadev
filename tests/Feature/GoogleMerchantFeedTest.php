<?php

use App\Models\Product;
use App\Models\ProductSku;
use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates google merchant xml feed', function () {
    Product::factory()->create([
        'is_active' => true,
        'sku' => 'TEST-123',
        'name' => 'Test Product',
        'description' => 'Test description',
    ]);

    $response = $this->get('/feed/google-merchant.xml');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');

    $xml = $response->getContent();
    expect($xml)
        ->toContain('<g:id>TEST-123</g:id>')
        ->toContain('<g:title>Test Product</g:title>')
        ->toContain('<g:price>');
});

it('generates variants in google merchant xml feed', function () {
    $product = Product::factory()->create([
        'is_active' => true,
        'sku' => 'PARENT-SKU',
        'name' => 'Parent Apparel',
        'description' => 'Great parent product',
    ]);

    $type = VariationType::factory()->create([
        'name' => 'Colore',
        'presentation_type' => 'color_swatch',
    ]);

    $option = VariationOption::factory()->create([
        'variation_type_id' => $type->id,
        'name' => 'Rosso',
    ]);

    $sku = ProductSku::factory()->create([
        'product_id' => $product->id,
        'sku' => 'PARENT-SKU-ROSSO',
        'is_available' => true,
    ]);

    $sku->options()->attach($option);

    $response = $this->get('/feed/google-merchant.xml');

    $response->assertStatus(200);
    $xml = $response->getContent();

    expect($xml)
        ->toContain('<g:id>PARENT-SKU-ROSSO</g:id>')
        ->toContain('<g:title>Parent Apparel (Rosso)</g:title>')
        ->toContain('<g:color>Rosso</g:color>')
        ->toContain('<g:item_group_id>PARENT-SKU</g:item_group_id>')
        ->toContain('<g:gender>unisex</g:gender>')
        ->toContain('<g:age_group>adult</g:age_group>');
});

it('sets kids age group for junior products in feed', function () {
    Product::factory()->create([
        'is_active' => true,
        'sku' => 'KID-123',
        'name' => 'T-shirt Junior Basic',
        'description' => 'Junior kids apparel',
    ]);

    $response = $this->get('/feed/google-merchant.xml');

    $response->assertStatus(200);
    $xml = $response->getContent();

    expect($xml)
        ->toContain('<g:id>KID-123</g:id>')
        ->toContain('<g:title>T-shirt Junior Basic</g:title>')
        ->toContain('<g:age_group>kids</g:age_group>');
});
