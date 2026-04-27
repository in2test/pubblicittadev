<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\CategoryQuantityDiscount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryDiscountModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_cascade_on_delete(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root']);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $root->id]);

        $discount = CategoryQuantityDiscount::create([
            'category_id' => $child->id,
            'min_quantity' => 2,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 5,
            'description' => 'test',
        ]);

        // Delete child category and expect cascade delete on discounts
        $child->delete();
        $this->assertDatabaseMissing('category_quantity_discounts', ['id' => $discount->id]);
    }
}
