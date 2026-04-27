<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_index_returns_products_without_search(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root', 'description' => null]);
        Product::factory()->create(['is_active' => true, 'category_id' => $root->id]);
        Product::factory()->create(['is_active' => true, 'category_id' => $root->id]);

        $response = $this->get(route('catalog'));

        $response->assertOk();
        $response->assertViewHas('products');
    }

    public function test_catalog_index_returns_inactive_products_excluded(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root', 'description' => null]);
        Product::factory()->create(['is_active' => true, 'category_id' => $root->id]);
        Product::factory()->create(['is_active' => false, 'category_id' => $root->id]);

        $response = $this->get(route('catalog'));

        $response->assertOk();
        $products = $response->viewData('products');
        $this->assertEquals(1, $products->total());
    }

    public function test_search_finds_products_by_name(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root', 'description' => null]);
        Product::factory()->create(['is_active' => true, 'name' => 'Red T-Shirt', 'category_id' => $root->id]);
        Product::factory()->create(['is_active' => true, 'name' => 'Blue Hoodie', 'category_id' => $root->id]);

        $response = $this->get(route('catalog', ['search' => 'T-Shirt']));

        $response->assertOk();
    }

    public function test_search_finds_products_by_sku(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root', 'description' => null]);
        Product::factory()->create(['is_active' => true, 'sku' => 'SKU-123456', 'category_id' => $root->id]);

        $response = $this->get(route('catalog', ['search' => 'SKU-123456']));

        $response->assertOk();
    }

    public function test_search_finds_products_by_description(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root', 'description' => null]);
        Product::factory()->create([
            'is_active' => true,
            'description' => 'Cotton blend fabric',
            'category_id' => $root->id,
        ]);

        $response = $this->get(route('catalog', ['search' => 'cotton']));

        $response->assertOk();
    }

    public function test_search_is_case_insensitive(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root', 'description' => null]);
        Product::factory()->create(['is_active' => true, 'name' => 'Baseball Cap', 'category_id' => $root->id]);

        $response = $this->get(route('catalog', ['search' => 'BASEBALL']));

        $response->assertOk();
    }

    public function test_search_returns_empty_when_no_results(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root', 'description' => null]);
        Product::factory()->create(['is_active' => true, 'name' => 'Widget', 'category_id' => $root->id]);

        $response = $this->get(route('catalog', ['search' => 'NonexistentProduct']));

        $response->assertOk();
    }

    public function test_search_preserves_pagination_params(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root', 'description' => null]);
        Product::factory()->count(20)->create(['is_active' => true, 'category_id' => $root->id]);

        $response = $this->get(route('catalog', ['search' => 'Test', 'page' => 2]));

        $response->assertOk();
        $response->assertViewHas('search', 'Test');
    }

    public function test_category_show_filters_by_category(): void
    {
        $parent = Category::create(['name' => 'Apparel', 'slug' => 'apparel', 'description' => null]);
        $child = Category::create(['name' => 'Shirts', 'slug' => 'shirts', 'parent_id' => $parent->id, 'description' => null]);
        $other = Category::create(['name' => 'Other', 'slug' => 'other', 'description' => null]);

        Product::factory()->create(['is_active' => true, 'category_id' => $child->id]);
        Product::factory()->create(['is_active' => true, 'category_id' => $other->id]);

        $response = $this->get(route('category', $parent->slug));

        $response->assertOk();
    }

    public function test_category_show_scopes_search_to_category_tree(): void
    {
        $parent = Category::create(['name' => 'Apparel', 'slug' => 'apparel', 'description' => null]);
        $child = Category::create(['name' => 'Shirts', 'slug' => 'shirts', 'parent_id' => $parent->id, 'description' => null]);
        $grandchild = Category::create(['name' => 'T-Shirts', 'slug' => 't-shirts', 'parent_id' => $child->id, 'description' => null]);

        Product::factory()->create(['is_active' => true, 'category_id' => $grandchild->id, 'name' => 'Target Product']);

        $response = $this->get(route('category', $parent->slug), ['search' => 'Target']);

        $response->assertOk();
    }

    public function test_category_show_returns_404_for_unknown_slug(): void
    {
        $response = $this->get(route('category', 'nonexistent-category'));

        $response->assertNotFound();
    }
}
