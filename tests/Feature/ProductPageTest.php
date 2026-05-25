<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use App\Models\VariationOption;
use App\Models\VariationType;
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

        $loadedOption = $loadedSku->options->first();
        $this->assertTrue($loadedOption->relationLoaded('type'));
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
        $opt115g = VariationOption::where('name', '115g Patinata Lucida')->firstOrFail();
        $optGrafica = VariationOption::where('name', 'Fronte e retro uguali')->firstOrFail();

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
}
