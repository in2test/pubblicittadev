<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use App\Models\VariationOption;
use App\Models\VariationType;
use App\Services\CartManager;
use Database\Seeders\StandardProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_page_returns_product_details(): void
    {
        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel', 'description' => null]);
        $product = Product::factory()->create([
            'is_active' => true,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'category_id' => $category->id,
            'price' => 75,
        ]);

        $response = $this->get(route('product', ['category' => $category->slug, 'product' => $product->slug]));

        $response->assertOk();
        $response->assertViewHas('product');
    }

    public function test_inactive_product_returns_404(): void
    {
        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel', 'description' => null]);
        $product = Product::factory()->create([
            'is_active' => false,
            'slug' => 'inactive-product',
            'category_id' => $category->id,
        ]);

        $response = $this->get(route('product', ['category' => $category->slug, 'product' => $product->slug]));

        $response->assertNotFound();
    }

    public function test_product_page_eager_loads_relationships(): void
    {
        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel', 'description' => null]);
        $product = Product::factory()->create([
            'is_active' => true,
            'slug' => 'test-product',
            'category_id' => $category->id,
        ]);

        $response = $this->get(route('product', ['category' => $category->slug, 'product' => $product->slug]));

        $response->assertOk();
        $productData = $response->viewData('product');
        $this->assertTrue($productData->relationLoaded('variationTypes'));
        $this->assertTrue($productData->relationLoaded('skus'));
        $this->assertTrue($productData->relationLoaded('pricingTiers'));
    }

    public function test_product_page_with_skus_and_variation_options(): void
    {
        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel', 'description' => null]);
        $product = Product::factory()->create([
            'is_active' => true,
            'slug' => 'test-product-with-skus',
            'category_id' => $category->id,
        ]);

        $colorType = VariationType::create([
            'name' => 'Color',
            'presentation_type' => 'color_swatch',
        ]);

        $colorOption = VariationOption::create([
            'variation_type_id' => $colorType->id,
            'name' => 'Red',
            'value' => '#FF0000',
        ]);

        $sku = ProductSku::create([
            'product_id' => $product->id,
            'sku' => 'TEST-SKU-RED',
            'quantity' => 10,
            'is_available' => true,
        ]);

        $sku->options()->attach($colorOption->id);

        $response = $this->get(route('product', ['category' => $category->slug, 'product' => $product->slug]));

        $response->assertOk();
        $productData = $response->viewData('product');
        $this->assertTrue($productData->relationLoaded('skus'));

        $loadedSku = $productData->skus->first();
        $this->assertTrue($loadedSku->relationLoaded('options'));

        $loadedSku->options->first();
        // The 'type' relationship was removed from eager-loading for performance reasons as it's not used in views.
    }

    public function test_product_page_with_no_variations(): void
    {
        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel', 'description' => null]);
        $product = Product::factory()->create([
            'is_active' => true,
            'slug' => 'no-variations',
            'category_id' => $category->id,
        ]);

        $response = $this->get(route('product', ['category' => $category->slug, 'product' => $product->slug]));

        $response->assertOk();
    }

    public function test_product_page_returns_404_for_unknown_product(): void
    {
        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel', 'description' => null]);

        $response = $this->get(route('product', ['category' => $category->slug, 'product' => 'unknown-product']));

        $response->assertNotFound();
    }

    public function test_product_page_returns_404_for_unknown_category(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->get(route('product', ['category' => 'unknown', 'product' => $product->slug]));

        $response->assertNotFound();
    }

    public function test_admin_can_view_inactive_product_page(): void
    {
        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel', 'description' => null]);
        $product = Product::factory()->create([
            'is_active' => false,
            'slug' => 'inactive-product',
            'category_id' => $category->id,
        ]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

        $response = $this->actingAs($admin)->get(route('product', ['category' => $category->slug, 'product' => $product->slug]));

        $response->assertOk();
        $response->assertViewHas('product');
    }

    public function test_livewire_product_price(): void
    {
        $this->seed(StandardProductSeeder::class);
        $product = Product::where('slug', 'volantini-flyer')->firstOrFail();
        $category = $product->category;

        $optA5 = VariationOption::where('name', 'A5 (14,8×21 cm)')->firstOrFail();
        $opt115g = VariationOption::where('name', 'Patinata Lucida 115 gr')->firstOrFail();

        $typeGrafica = VariationType::where('name', 'Lato di Stampa')->firstOrFail();
        $optGrafica = VariationOption::where('name', 'Fronte/Retro Stessa Grafica')
            ->where('variation_type_id', $typeGrafica->id)
            ->firstOrFail();

        // Find active SKU
        $activeSku = $product->getActiveSku([
            $optA5->variation_type_id => $optA5->id,
            $opt115g->variation_type_id => $opt115g->id,
            $optGrafica->variation_type_id => $optGrafica->id,
        ]);

        // Mount the Livewire component!
        Livewire::test('⚡product', [
            'product' => $product,
            'category' => $category,
            'options' => [
                $optA5->variation_type_id => $optA5->id,
                $opt115g->variation_type_id => $opt115g->id,
                $optGrafica->variation_type_id => $optGrafica->id,
            ],
        ])
            ->set('quantities', [$activeSku->id => 100])
            ->assertSet('totalPrice', 11.00);
    }

    public function test_livewire_product_custom_format(): void
    {
        $this->seed(StandardProductSeeder::class);
        $product = Product::where('slug', 'biglietti-da-visita')->firstOrFail();
        $category = $product->category;

        $product->update([
            'allows_custom_size' => true,
            'min_custom_width' => 30,
            'max_custom_width' => 100,
            'min_custom_height' => 30,
            'max_custom_height' => 100,
        ]);

        $typeFormato = VariationType::where('name', 'Formato')->firstOrFail();
        $typeFinitura = VariationType::where('name', 'Finitura')->firstOrFail();
        $optPlast = VariationOption::where('variation_type_id', $typeFinitura->id)->firstOrFail();

        $activeSku = $product->getActiveSku([
            $typeFormato->id => 999999, // custom format virtual ID
            $typeFinitura->id => $optPlast->id,
        ]);

        $this->assertNotNull($activeSku);

        // Mount the Livewire component!
        $component = Livewire::test('⚡product', [
            'product' => $product,
            'category' => $category,
            'options' => [
                $typeFormato->id => 999999,
                $typeFinitura->id => $optPlast->id,
            ],
        ]);

        // Quantities should be set for the active SKU
        $component->set('quantities', [$activeSku->id => 100])
            ->set('width', 45)
            ->set('height', 45);

        // Verify total price is computed
        $component->assertSet('width', 45)
            ->assertSet('height', 45)
            ->assertSet('totalPrice', 25.92);

        // Try to add to cart
        $component->call('addToCart');

        // Verify item is in cart with custom width and height
        $cartItems = app(CartManager::class)->getItems();
        $this->assertNotEmpty($cartItems);

        $cartItem = collect($cartItems)->first();
        $this->assertEquals(45, $cartItem['width']);
        $this->assertEquals(45, $cartItem['height']);
    }

    public function test_livewire_product_custom_format_validation_out_of_bounds(): void
    {
        $this->seed(StandardProductSeeder::class);
        $product = Product::where('slug', 'biglietti-da-visita')->firstOrFail();
        $category = $product->category;

        $product->update([
            'allows_custom_size' => true,
            'min_custom_width' => 30,
            'max_custom_width' => 100,
            'min_custom_height' => 30,
            'max_custom_height' => 100,
        ]);

        $typeFormato = VariationType::where('name', 'Formato')->firstOrFail();
        $typeFinitura = VariationType::where('name', 'Finitura')->firstOrFail();
        $optPlast = VariationOption::where('variation_type_id', $typeFinitura->id)->firstOrFail();

        // Mount the Livewire component!
        $component = Livewire::test('⚡product', [
            'product' => $product,
            'category' => $category,
            'options' => [
                $typeFormato->id => 999999,
                $typeFinitura->id => $optPlast->id,
            ],
        ]);

        // Try setting out of bounds width
        $component->set('width', 120)
            ->call('validateDimensions')
            ->assertHasErrors(['width']);

        // Try setting out of bounds height
        $component->set('width', 50)
            ->set('height', 120)
            ->call('validateDimensions')
            ->assertHasErrors(['height']);
    }

    public function test_livewire_product_custom_format_rounding_and_under_tier_price(): void
    {
        $this->seed(StandardProductSeeder::class);
        $product = Product::where('slug', 'biglietti-da-visita')->firstOrFail();
        $category = $product->category;

        $product->update([
            'allows_custom_size' => true,
            'min_custom_width' => 30,
            'max_custom_width' => 100,
            'min_custom_height' => 30,
            'max_custom_height' => 100,
        ]);

        $typeFormato = VariationType::where('name', 'Formato')->firstOrFail();
        $typeFinitura = VariationType::where('name', 'Finitura')->firstOrFail();
        $optPlast = VariationOption::where('variation_type_id', $typeFinitura->id)->firstOrFail();

        // Mount the Livewire component!
        $component = Livewire::test('⚡product', [
            'product' => $product,
            'category' => $category,
            'options' => [
                $typeFormato->id => 999999,
                $typeFinitura->id => $optPlast->id,
            ],
        ]);

        // Set dimensions to 30x30 mm
        $component->set('width', 30)
            ->set('height', 30);

        $itemsPerSheet = $component->get('itemsPerSheet');
        $this->assertGreaterThan(0, $itemsPerSheet);

        $activeSku = $product->getActiveSku([
            $typeFormato->id => 999999,
            $typeFinitura->id => $optPlast->id,
        ]);

        // Setting quantities below tier minimum or itemsPerSheet should round up to itemsPerSheet
        $component->set("quantities.{$activeSku->id}", 50);

        $quantities = $component->get('quantities');
        $this->assertEquals($itemsPerSheet, $quantities[$activeSku->id]);

        // Price should be computed correctly using fallback to 100 pz tier unit price
        $totalPrice = $component->get('totalPrice');
        $this->assertGreaterThan(0, $totalPrice);
    }

    public function test_livewire_product_custom_format_reverts_on_standard_match(): void
    {
        $this->seed(StandardProductSeeder::class);
        $product = Product::where('slug', 'biglietti-da-visita')->firstOrFail();
        $category = $product->category;

        $product->update(['allows_custom_size' => true]);

        $typeFormato = VariationType::where('name', 'Formato')->firstOrFail();
        $typeFinitura = VariationType::where('name', 'Finitura')->firstOrFail();
        $optPlast = VariationOption::where('variation_type_id', $typeFinitura->id)->firstOrFail();

        // Mount the Livewire component!
        $component = Livewire::test('⚡product', [
            'product' => $product,
            'category' => $category,
            'options' => [
                $typeFormato->id => 999999,
                $typeFinitura->id => $optPlast->id,
            ],
        ]);

        // "85x55 mm" is standard format option. Let's find its option ID
        $opt85x55 = VariationOption::where('variation_type_id', $typeFormato->id)
            ->where('name', '85x55 mm')
            ->firstOrFail();

        // Set dimensions to 55 x 85 mm (reversed 85x55)
        $component->set('width', 55)
            ->set('height', 85);

        // It should automatically match, set the selection to $opt85x55->id and reset width/height to null!
        $component->assertSet("selectedOptions.{$typeFormato->id}", $opt85x55->id)
            ->assertSet('width', null)
            ->assertSet('height', null);
    }

    public function test_product_page_includes_open_graph_meta_tags(): void
    {
        $category = Category::create(['name' => 'Shirts', 'slug' => 'camicie', 'description' => null]);
        $product = Product::factory()->create([
            'is_active' => true,
            'name' => 'Cambridge Shirt',
            'slug' => 'cambridge',
            'category_id' => $category->id,
            'description' => 'A fine cotton shirt.',
        ]);

        $siteName = config('app.name', 'Pubblicittà24');

        $response = $this->get(route('product', ['category' => $category->slug, 'product' => $product->slug]));

        $response->assertOk();
        $response->assertSee('<meta property="og:type" content="product">', false);
        $response->assertSee('<meta property="og:site_name" content="'.$siteName.'">', false);
        $response->assertSee('<meta property="og:title" content="Cambridge Shirt | '.$siteName.'">', false);
        $response->assertSee('<meta property="og:url" content="'.$product->url.'">', false);
        $response->assertSee('<meta property="og:image" content="'.$product->getFirstImageUrl('large').'">', false);
        $response->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
        $response->assertSee('<meta name="twitter:image" content="'.$product->getFirstImageUrl('large').'">', false);
    }
}
