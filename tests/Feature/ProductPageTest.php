<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $response = $this->get(route('product', ['category' => $category->slug, 'slug' => $product->slug]));

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

        $response = $this->get(route('product', ['category' => $category->slug, 'slug' => $product->slug]));

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

        $response = $this->get(route('product', ['category' => $category->slug, 'slug' => $product->slug]));

        $response->assertOk();
        $productData = $response->viewData('product');
        $this->assertTrue($productData->relationLoaded('variations'));
        $this->assertTrue($productData->relationLoaded('pricingTiers'));
    }

    public function test_product_page_with_no_variations(): void
    {
        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel', 'description' => null]);
        $product = Product::factory()->create([
            'is_active' => true,
            'slug' => 'no-variations',
            'category_id' => $category->id,
        ]);

        $response = $this->get(route('product', ['category' => $category->slug, 'slug' => $product->slug]));

        $response->assertOk();
    }

    public function test_product_page_returns_404_for_unknown_product(): void
    {
        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel', 'description' => null]);

        $response = $this->get(route('product', ['category' => $category->slug, 'slug' => 'unknown-product']));

        $response->assertNotFound();
    }

    public function test_product_page_returns_404_for_unknown_category(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->get(route('product', ['category' => 'unknown', 'slug' => $product->slug]));

        $response->assertNotFound();
    }
}
