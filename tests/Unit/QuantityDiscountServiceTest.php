<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\CategoryQuantityDiscount;
use App\Services\QuantityDiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuantityDiscountServiceTest extends TestCase
{
    use RefreshDatabase;

    protected QuantityDiscountService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuantityDiscountService;
    }

    public function test_returns_null_when_no_category(): void
    {
        $price = $this->service->computeDiscountedPrice(100.0, null);
        $this->assertEquals(100.0, $price);
    }

    public function test_returns_null_when_category_id_is_zero(): void
    {
        $discount = $this->service->getDiscountForCategoryTree(0, 10);
        $this->assertNull($discount);
    }

    public function test_returns_null_when_no_discount_exists(): void
    {
        $category = Category::create(['name' => 'No Discount', 'slug' => 'no-discount']);

        $discount = $this->service->getDiscountForCategoryTree($category->id, 10);
        $this->assertNull($discount);
    }

    public function test_uses_closest_parent_discount_when_child_has_none(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root']);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $root->id]);

        CategoryQuantityDiscount::create([
            'category_id' => $root->id,
            'min_quantity' => 5,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'description' => 'Root discount',
        ]);

        $discount = $this->service->getDiscountForCategoryTree($child->id, 10);
        $this->assertNotNull($discount);
        $this->assertEquals('Root discount', $discount->description);
    }

    public function test_uses_child_discount_preferentially(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root']);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $root->id]);

        CategoryQuantityDiscount::create([
            'category_id' => $root->id,
            'min_quantity' => 5,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 5,
            'description' => 'Root 5%',
        ]);

        CategoryQuantityDiscount::create([
            'category_id' => $child->id,
            'min_quantity' => 5,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'description' => 'Child 10%',
        ]);

        $discount = $this->service->getDiscountForCategoryTree($child->id, 10);
        $this->assertEquals('Child 10%', $discount->description);
    }

    public function test_uses_highest_min_quantity_when_multiple_match(): void
    {
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat']);

        CategoryQuantityDiscount::create([
            'category_id' => $category->id,
            'min_quantity' => 5,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 5,
            'description' => '5+',
        ]);

        CategoryQuantityDiscount::create([
            'category_id' => $category->id,
            'min_quantity' => 10,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 15,
            'description' => '10+',
        ]);

        $discount = $this->service->getDiscountForCategoryTree($category->id, 10);
        $this->assertEquals('10+', $discount->description);
    }

    public function test_uses_highest_discount_value_when_equal_min_quantity(): void
    {
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat']);

        CategoryQuantityDiscount::create([
            'category_id' => $category->id,
            'min_quantity' => 5,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 5,
            'description' => '5% at 5+',
        ]);

        CategoryQuantityDiscount::create([
            'category_id' => $category->id,
            'min_quantity' => 5,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'description' => '10% at 5+',
        ]);

        $discount = $this->service->getDiscountForCategoryTree($category->id, 10);
        $this->assertEquals('10% at 5+', $discount->description);
    }

    public function test_fixed_discount_subtracts_from_base_price(): void
    {
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat']);

        $discount = CategoryQuantityDiscount::create([
            'category_id' => $category->id,
            'min_quantity' => 1,
            'max_quantity' => null,
            'discount_type' => 'fixed',
            'discount_value' => 5,
            'description' => '$5 off',
        ]);

        $price = $this->service->computeDiscountedPrice(20.0, $discount);
        $this->assertEquals(15.0, $price);
    }

    public function test_fixed_discount_caps_at_zero(): void
    {
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat']);

        $discount = CategoryQuantityDiscount::create([
            'category_id' => $category->id,
            'min_quantity' => 1,
            'max_quantity' => null,
            'discount_type' => 'fixed',
            'discount_value' => 50,
            'description' => '$50 off',
        ]);

        $price = $this->service->computeDiscountedPrice(30.0, $discount);
        $this->assertEquals(0.0, $price);
    }

    public function test_percent_discount_caps_at_zero(): void
    {
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat']);

        $discount = CategoryQuantityDiscount::create([
            'category_id' => $category->id,
            'min_quantity' => 1,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 150,
            'description' => '150% off',
        ]);

        $price = $this->service->computeDiscountedPrice(10.0, $discount);
        $this->assertEquals(0.0, $price);
    }

    public function test_multi_level_category_tree(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root']);
        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent', 'parent_id' => $root->id]);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $parent->id]);

        CategoryQuantityDiscount::create([
            'category_id' => $root->id,
            'min_quantity' => 5,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 5,
            'description' => 'Root only',
        ]);

        $discount = $this->service->getDiscountForCategoryTree($child->id, 10);
        $this->assertEquals('Root only', $discount->description);
    }
}
