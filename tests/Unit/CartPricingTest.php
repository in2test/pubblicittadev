<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\CategoryQuantityDiscount;
use App\Models\Product;
use App\Services\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_total_uses_discounts(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root']);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $root->id]);

        CategoryQuantityDiscount::create([
            'category_id' => $child->id,
            'min_quantity' => 5,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'description' => 'child discount',
        ]);

        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 100,
            'category_id' => $child->id,
            'offer_price' => 0,
        ]);

        $cart = new CartManager;
        $cart->clear();
        $cart->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'image_url' => null,
            'price' => 100,
            'quantity' => 5,
        ]);

        $this->assertEquals(450.0, $cart->total());
    }
}
