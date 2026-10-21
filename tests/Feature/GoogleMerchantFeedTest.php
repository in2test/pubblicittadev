<?php

use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\CategoryQuantityDiscount;
use App\Models\Image;
use App\Models\PricingTier;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\VariationOption;
use App\Models\VariationType;
use App\Services\GoogleMerchantFeedBuilder;
use App\Services\QuantityDiscountService;
use Carbon\CarbonImmutable;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use function libxml_get_errors;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::store((string) config('services.google.feed_cache_store'))->flush();
});

it('generates google merchant xml feed', function () {
    $brand = Brand::factory()->create(['name' => 'Clique']);

    $product = Product::factory()->create([
        'is_active' => true,
        'sku' => 'TEST-123',
        'name' => 'Test Product',
        'description' => 'Test description',
        'brand_id' => $brand->id,
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
        ->toContain('<g:brand>Clique</g:brand>')
        ->toContain('<g:google_product_category>Apparel &amp; Accessories &gt; Clothing</g:google_product_category>');

    $feed = simplexml_load_string($xml);
    expect($feed)->toBeInstanceOf(SimpleXMLElement::class);

    $merchantNamespace = $feed->getNamespaces(true)['g'];
    $item = $feed->channel->item[0]->children($merchantNamespace);
    expect((string) $item->image_link)->toBe($imageUrl)
        ->and((string) $item->additional_image_link[0])->toBe($additionalImageUrl);
});

it('validates the Google Merchant feed XML against the official product XSD', function () {
    $builder = app(GoogleMerchantFeedBuilder::class);
    $xml = $builder->build();

    $doc = new DOMDocument;
    $doc->preserveWhiteSpace = false;
    $doc->formatOutput = false;
    $doc->loadXML($xml);

    libxml_use_internal_errors(true);
    $xsdUrl = base_path('resources/xsd/google_product.xsd');
    $valid = $doc->schemaValidate($xsdUrl);

    if (! $valid) {
        $errors = libxml_get_errors();
        $msg = implode("\n", array_map(fn (LibXMLError $e) => trim($e->message)." (line {$e->line})", $errors));
        libxml_clear_errors();
        $this->fail("Google Merchant feed XML does not validate against the XSD:\n{$msg}");
    }

    expect($valid)->toBeTrue('Feed must conform to Google product XSD');
});

it('generates variants in google merchant xml feed', function () {
    $brand = Brand::factory()->create(['name' => 'Clique']);

    $product = Product::factory()->create([
        'is_active' => true,
        'sku' => 'PARENT-SKU',
        'name' => 'Parent Apparel',
        'description' => 'Great parent product',
        'brand_id' => $brand->id,
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
        ->toContain('<g:brand>Clique</g:brand>')
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

    $now = CarbonImmutable::now();
    $campaign = Campaign::factory()->create([
        'name' => 'Outlet Campaign',
        'starts_at' => $now,
        'ends_at' => $now->addHour(),
    ]);
    $campaign->products()->attach($product->id);
    $campaign->outletSkus()->attach($sku->id);

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
    $brand = Brand::factory()->create(['name' => 'Clique']);

    Product::factory()->create([
        'is_active' => true,
        'sku' => 'KID-123',
        'name' => 'T-shirt Junior Basic',
        'description' => 'Junior kids apparel',
        'brand_id' => $brand->id,
    ]);

    $response = $this->get('/feed/google-merchant.xml');

    $response->assertStatus(200);
    $xml = $response->getContent();

    expect($xml)
        ->toContain('<g:id>KID-123</g:id>')
        ->toContain('<g:title>T-shirt Junior Basic</g:title>')
        ->toContain('<g:brand>Clique</g:brand>')
        ->toContain('<g:age_group>kids</g:age_group>');
});

it('includes extended apparel details (material, highlights, details, italian description) in Google Merchant feed', function () {
    $product = Product::factory()->create([
        'is_active' => true,
        'type' => Product::TYPE_NEWWAVE,
        'sku' => 'NWG-FEED-TEST',
        'name' => 'Giacca Tecnica Softshell',
        'description' => 'Giacca versatile antivento e traspirante.',
        'technical_specs' => '100% poliestere ring-spun traspirante',
        'construction_features' => 'Etichetta strappabile (Easy cut), Asciugatura rapida',
        'certifications' => 'Oeko-Tex Standard 100, Protezione UV',
        'customization_notes' => 'Lavaggio in lavatrice a 40°C, Non candeggiare, Stirare a max 110°C',
    ]);

    $imageUrl = 'https://images.example/nwg-product.jpg?width=600';
    Image::create([
        'product_id' => $product->id,
        'image_url' => $imageUrl,
        'large_url' => $imageUrl,
        'order_by' => 1,
    ]);

    $response = $this->get('/feed/google-merchant.xml');
    $response->assertStatus(200);

    $xml = $response->getContent();

    expect($xml)
        ->toContain('<g:id>NWG-FEED-TEST</g:id>')
        ->toContain('<g:material>100% poliestere ring-spun traspirante</g:material>')
        ->toContain('<g:product_highlight>Materiale: 100% poliestere ring-spun traspirante</g:product_highlight>')
        ->toContain('<g:product_highlight>Certificazione: Oeko-Tex Standard 100</g:product_highlight>')
        ->toContain('<g:product_detail>')
        ->toContain('<g:attribute_name>Materiale</g:attribute_name>')
        ->toContain('<g:attribute_name>Certificazioni</g:attribute_name>')
        ->toContain('Composizione: 100% poliestere ring-spun traspirante')
        ->toContain('Certificazioni: Oeko-Tex Standard 100, Protezione UV')
        ->toContain('Cura del capo: Lavaggio in lavatrice a 40°C');

    // Also assert it validates against the official XSD
    $doc = new DOMDocument;
    $doc->preserveWhiteSpace = false;
    $doc->formatOutput = false;
    $doc->loadXML($xml);

    libxml_use_internal_errors(true);
    $xsdUrl = base_path('resources/xsd/google_product.xsd');
    $valid = $doc->schemaValidate($xsdUrl);
    expect($valid)->toBeTrue();
});

it('builds the feed without a query per product and per sku', function () {
    $category = Category::factory()->create();
    $campaign = Campaign::factory()->create();

    foreach (range(1, 5) as $index) {
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $category->id,
            'sku' => 'PERF-'.$index,
        ]);

        PricingTier::create([
            'product_id' => $product->id,
            'product_sku_id' => null,
            'min_quantity' => 1,
            'price_per_unit' => 10.00,
        ]);

        foreach (range(1, 4) as $skuIndex) {
            $sku = ProductSku::factory()->create([
                'product_id' => $product->id,
                'sku' => 'PERF-'.$index.'-'.$skuIndex,
                'is_available' => true,
                'is_outlet' => true,
            ]);

            PricingTier::create([
                'product_id' => $product->id,
                'product_sku_id' => $sku->id,
                'min_quantity' => 1,
                'price_per_unit' => 9.00,
            ]);

            $sku->campaigns()->attach($campaign->id);
        }
    }

    DB::enableQueryLog();

    app(GoogleMerchantFeedBuilder::class)->build();

    $queryCount = count(DB::getQueryLog());

    // The feed used to issue one query per SKU for images, pricing tiers and
    // campaigns, which made the request exceed the maximum execution time.
    expect($queryCount)->toBeLessThan(40);
});
