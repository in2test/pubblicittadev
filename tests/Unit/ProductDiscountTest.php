<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\CategoryQuantityDiscount;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDiscountTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_base_price_when_no_discount(): void
    {
        $product = Product::factory()->create(['price' => 50]);
        $this->assertEquals(50.0, $product->getPriceForQuantity(1));
    }

    public function test_returns_base_price_when_no_category(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root', 'description' => null]);
        $product = Product::factory()->create(['price' => 50, 'category_id' => $root->id]);
        $this->assertEquals(50.0, $product->getPriceForQuantity(1));
    }

    public function test_returns_zero_when_price_is_zero(): void
    {
        $product = Product::factory()->create(['price' => 0]);
        $this->assertEquals(0.0, $product->getPriceForQuantity(1));
    }

    public function test_returns_zero_when_price_is_negative(): void
    {
        $product = Product::factory()->create(['price' => -10]);
        $this->assertEquals(0.0, $product->getPriceForQuantity(1));
    }

    public function test_applies_child_category_discount(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root']);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $root->id]);

        CategoryQuantityDiscount::create([
            'category_id' => $child->id,
            'min_quantity' => 10,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'description' => '10% at 10+',
        ]);

        $product = Product::factory()->create(['price' => 100, 'category_id' => $child->id]);
        $this->assertEquals(90.0, $product->getPriceForQuantity(10));
    }

    public function test_applies_root_category_discount_when_no_child_discount(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root']);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $root->id]);

        CategoryQuantityDiscount::create([
            'category_id' => $root->id,
            'min_quantity' => 5,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 20,
            'description' => '20% at 5+',
        ]);

        $product = Product::factory()->create(['price' => 100, 'category_id' => $child->id]);
        $this->assertEquals(80.0, $product->getPriceForQuantity(5));
    }

    public function test_uses_best_discount_in_category_tree(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root']);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $root->id]);

        CategoryQuantityDiscount::create([
            'category_id' => $root->id,
            'min_quantity' => 1,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 5,
            'description' => 'Root 5%',
        ]);

        CategoryQuantityDiscount::create([
            'category_id' => $child->id,
            'min_quantity' => 1,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 15,
            'description' => 'Child 15%',
        ]);

        $product = Product::factory()->create(['price' => 100, 'category_id' => $child->id]);
        $this->assertEquals(85.0, $product->getPriceForQuantity(1));
    }

    public function test_applies_fixed_discount(): void
    {
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat']);

        CategoryQuantityDiscount::create([
            'category_id' => $category->id,
            'min_quantity' => 5,
            'max_quantity' => null,
            'discount_type' => 'fixed',
            'discount_value' => 10,
            'description' => '$10 off',
        ]);

        $product = Product::factory()->create(['price' => 50, 'category_id' => $category->id]);
        $this->assertEquals(40.0, $product->getPriceForQuantity(5));
    }

    public function test_quantity_below_minimum_uses_no_discount(): void
    {
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat']);

        CategoryQuantityDiscount::create([
            'category_id' => $category->id,
            'min_quantity' => 10,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'description' => '10% at 10+',
        ]);

        $product = Product::factory()->create(['price' => 100, 'category_id' => $category->id]);
        $this->assertEquals(100.0, $product->getPriceForQuantity(5));
    }

    public function test_returns_base_price_when_service_throws(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root', 'description' => null]);
        $product = Product::factory()->create(['price' => 50, 'category_id' => $root->id]);
        $this->assertEquals(50.0, $product->getPriceForQuantity(1));
    }

    public function test_handles_root_category(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root', 'description' => null]);
        $product = Product::factory()->create(['price' => 50, 'category_id' => $root->id]);
        $this->assertEquals(50.0, $product->getPriceForQuantity(10));
    }
}
