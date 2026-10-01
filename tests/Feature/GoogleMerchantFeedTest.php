<?php

use App\Models\CategoryQuantityDiscount;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\VariationOption;
use App\Models\VariationType;
use App\Services\QuantityDiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates google merchant xml feed', function () {
    $product = Product::factory()->create([
        'is_active' => true,
        'sku' => 'TEST-123',
        'name' => 'Test Product',
        'description' => 'Test description',
    ]);
    $imageUrl = 'https://images.example/main.jpg?width=600&fit=crop';
    $additionalImageUrl = 'https://images.example/detail.jpg?width=600&fit=crop';

    Image::create([
        'product_id' => $product->id,
        'image_url' => $imageUrl,
        'large_url' => $imageUrl,
        'order_by' => 1,
    ]);

    Image::create([
        'product_id' => $product->id,
        'image_url' => $additionalImageUrl,
        'large_url' => $additionalImageUrl,
        'order_by' => 2,
    ]);

    $response = $this->get('/feed/google-merchant.xml');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');

    $xml = $response->getContent();
    expect($xml)
        ->toContain('<g:id>TEST-123</g:id>')
        ->toContain('<g:title>Test Product</g:title>')
        ->toContain('<g:price>')
        ->toContain('<g:brand>CLIQUE</g:brand>')
        ->toContain('<g:google_product_category>Apparel &amp; Accessories &gt; Clothing</g:google_product_category>');

    $feed = simplexml_load_string($xml);
    expect($feed)->toBeInstanceOf(SimpleXMLElement::class);

    $merchantNamespace = $feed->getNamespaces(true)['g'];
    $item = $feed->channel->item[0]->children($merchantNamespace);
    expect((string) $item->image_link)->toBe($imageUrl)
        ->and((string) $item->additional_image_link[0])->toBe($additionalImageUrl);
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
        ->toContain('<g:age_group>adult</g:age_group>')
        ->toContain('<g:brand>CLIQUE</g:brand>')
        ->toContain('<g:google_product_category>Apparel &amp; Accessories &gt; Clothing</g:google_product_category>');
});

it('exports valid xml and the effective outlet sku price', function () {
    $product = Product::factory()->create([
        'is_active' => true,
        'sku' => 'OUTLET-PARENT',
        'price' => 100,
    ]);

    CategoryQuantityDiscount::create([
        'category_id' => $product->category_id,
        'min_quantity' => 1,
        'discount_type' => 'percent',
        'discount_value' => 10,
    ]);
    QuantityDiscountService::clearCache();

    $sku = ProductSku::factory()->create([
        'product_id' => $product->id,
        'sku' => 'OUTLET-VARIANT',
        'is_outlet' => true,
        'override_price' => 50,
    ]);

    $imageUrl = 'https://images.example/product.jpg?width=600&fit=crop&w=1472';
    $additionalImageUrl = 'https://images.example/product-side.jpg?width=600&fit=crop&w=1472';

    Image::create([
        'product_id' => $product->id,
        'image_url' => $imageUrl,
        'large_url' => $imageUrl,
        'order_by' => 1,
    ]);

    Image::create([
        'product_id' => $product->id,
        'image_url' => $additionalImageUrl,
        'large_url' => $additionalImageUrl,
        'order_by' => 2,
    ]);

    $response = $this->get('/feed/google-merchant.xml');

    $response->assertSuccessful();
    $feed = simplexml_load_string($response->getContent());
    expect($feed)->toBeInstanceOf(SimpleXMLElement::class);

    $merchantNamespace = $feed->getNamespaces(true)['g'];
    $item = collect($feed->channel->item)
        ->first(fn (SimpleXMLElement $item): bool => (string) $item->children($merchantNamespace)->id === $sku->sku);
    $merchantItem = $item->children($merchantNamespace);

    expect((string) $merchantItem->price)->toBe('45.00 EUR')
        ->and((string) $merchantItem->image_link)->toBe($imageUrl)
        ->and((string) $merchantItem->additional_image_link[0])->toBe($additionalImageUrl);
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
        ->toContain('<g:brand>CLIQUE</g:brand>')
        ->toContain('<g:age_group>kids</g:age_group>');
});
