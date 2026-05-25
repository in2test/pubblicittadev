<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the homepage loads successfully and returns products.
     */
    public function test_homepage_loads_successfully(): void
    {
        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel']);
        Product::factory()->count(5)->create([
            'is_active' => true,
            'category_id' => $category->id,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertViewHas('products');

        $products = $response->viewData('products');
        $this->assertCount(5, $products);
    }

    /**
     * Test that the homepage eager loads required relationships.
     */
    public function test_homepage_eager_loads_relationships(): void
    {
        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel']);
        Product::factory()->create([
            'is_active' => true,
            'category_id' => $category->id,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $products = $response->viewData('products');

        foreach ($products as $product) {
            $this->assertTrue($product->relationLoaded('category'));
            $this->assertTrue($product->relationLoaded('variationTypes'));
            $this->assertTrue($product->relationLoaded('media'));
        }
    }

    /**
     * Test that the homepage correctly displays and renders dynamic variation colors on product cards.
     */
    public function test_homepage_renders_dynamic_variation_swatches(): void
    {
        $category = Category::create(['name' => 'Shirts', 'slug' => 'shirts']);

        // Create color type
        $colorType = VariationType::factory()->create([
            'name' => 'Color',
            'presentation_type' => 'color_swatch',
        ]);

        // Create color options
        $colorBlue = VariationOption::factory()->create([
            'variation_type_id' => $colorType->id,
            'name' => 'Blue Swatch',
            'value' => '#0000ff',
        ]);
        $colorRed = VariationOption::factory()->create([
            'variation_type_id' => $colorType->id,
            'name' => 'Red Swatch',
            'value' => '#ff0000',
        ]);

        // Create product
        $product = Product::factory()->create([
            'name' => 'Dynamic Blue-Red Shirt',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        // Attach color type to product (has_images = true)
        $product->variationTypes()->attach($colorType->id, [
            'has_images' => true,
            'sort_order' => 1,
        ]);

        // Attach option records (via product_variation_options)
        $productVariationType = $product->variationTypes()->first()->pivot;

        $productVariationType->options()->create([
            'variation_option_id' => $colorBlue->id,
            'price_modifier' => 0.00,
        ]);
        $productVariationType->options()->create([
            'variation_option_id' => $colorRed->id,
            'price_modifier' => 0.00,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Dynamic Blue-Red Shirt');

        // Assert that we see the color hex swatches and option names
        $response->assertSee('#0000ff');
        $response->assertSee('Blue Swatch');
        $response->assertSee('#ff0000');
        $response->assertSee('Red Swatch');
    }
}
