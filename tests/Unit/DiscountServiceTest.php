<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\CategoryQuantityDiscount;
use App\Services\QuantityDiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_resolution_with_child_and_root(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root', 'description' => null]);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $root->id, 'description' => null]);

        $childDiscount = CategoryQuantityDiscount::create([
            'category_id' => $child->id,
            'min_quantity' => 5,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'description' => 'child discount',
        ]);
        $rootDiscount = CategoryQuantityDiscount::create([
            'category_id' => $root->id,
            'min_quantity' => 3,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 20,
            'description' => 'root discount',
        ]);

        $service = new QuantityDiscountService;

        $disc = $service->getDiscountForCategoryTree($child->id, 6);
        $this->assertNotNull($disc);
        $this->assertEquals($childDiscount->id, $disc->id);
        $price = $service->computeDiscountedPrice(100.0, $disc);
        $this->assertEquals(90.0, $price);

        $disc2 = $service->getDiscountForCategoryTree($child->id, 4);
        $this->assertNotNull($disc2);
        $this->assertEquals($rootDiscount->id, $disc2->id);
        $price2 = $service->computeDiscountedPrice(100.0, $disc2);
        $this->assertEquals(80.0, $price2);
    }
}
