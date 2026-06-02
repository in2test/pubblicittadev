<?php

use App\Models\Product;
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
