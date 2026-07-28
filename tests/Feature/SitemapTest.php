<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_returns_xml_with_active_products_and_categories(): void
    {
        $category = Category::create(['name' => 'T-Shirts', 'slug' => 't-shirts']);
        $emptyCategory = Category::create(['name' => 'Empty Cat', 'slug' => 'empty-cat']);

        $activeProduct = Product::factory()->create([
            'is_active' => true,
            'slug' => 'active-product',
            'category_id' => $category->id,
        ]);

        $inactiveProduct = Product::factory()->create([
            'is_active' => false,
            'slug' => 'inactive-product',
            'category_id' => $category->id,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
        $response->assertSee($activeProduct->url);
        $response->assertDontSee($inactiveProduct->url);
        $response->assertSee(route('category', $category));
        $response->assertDontSee(route('category', $emptyCategory));
    }
}
