<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryQuantityDiscount;
use App\Models\Product;
use App\Services\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $cart = new CartManager;
        $cart->clear();
    }

    public function test_adds_item_to_cart(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $response = $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'quantity' => 2,
        ]);

        $response->assertRedirect(route('cart'));
        $response->assertSessionHas('success');

        $cart = new CartManager;
        $items = $cart->getItems();
        $this->assertNotEmpty($items);
    }

    public function test_add_item_applies_quantity_discount(): void
    {
        $root = Category::create(['name' => 'Root', 'slug' => 'root']);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $root->id]);

        CategoryQuantityDiscount::create([
            'category_id' => $child->id,
            'min_quantity' => 12,
            'max_quantity' => null,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'description' => '10% off at 12+',
        ]);

        $product = Product::factory()->create(['price' => 100, 'category_id' => $child->id]);

        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'quantity' => 12,
        ]);

        $cart = new CartManager;
        $items = $cart->getItems();
        $item = reset($items);
        $this->assertEquals(90.0, $item['price']);
    }

    public function test_add_item_with_print_placements(): void
    {
        $this->markTestSkipped('Requires print_placements pivot table setup');
    }

    public function test_add_item_with_color_and_size(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $response = $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'quantity' => 1,
            'color_id' => 1,
            'color_name' => 'Red',
            'size_id' => 2,
            'size_name' => 'L',
        ]);

        $response->assertRedirect(route('cart'));

        $cart = new CartManager;
        $items = $cart->getItems();
        $item = reset($items);
        $this->assertEquals('Red', $item['color_name']);
        $this->assertEquals('L', $item['size_name']);
    }

    public function test_add_item_validates_required_fields(): void
    {
        $response = $this->post(route('cart.add'), []);

        $response->assertSessionHasErrors(['product_id', 'quantity', 'product_name', 'product_slug']);
    }

    public function test_add_item_validates_product_exists(): void
    {
        $response = $this->post(route('cart.add'), [
            'product_id' => 99999,
            'product_name' => 'Test',
            'product_slug' => 'test',
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors(['product_id']);
    }

    public function test_update_cart_item_quantity(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'quantity' => 1,
        ]);

        $cart = new CartManager;
        $key = array_key_first($cart->getItems());

        $response = $this->put(route('cart.update'), [
            'key' => $key,
            'quantity' => 5,
        ]);

        $response->assertSessionHas('success');
        $items = $cart->getItems();
        $this->assertEquals(5, $items[$key]['quantity']);
    }

    public function test_update_cart_item_to_zero_removes_item(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'quantity' => 2,
        ]);

        $cart = new CartManager;
        $key = array_key_first($cart->getItems());

        $this->put(route('cart.update'), [
            'key' => $key,
            'quantity' => 0,
        ]);

        $items = $cart->getItems();
        $this->assertArrayNotHasKey($key, $items);
    }

    public function test_remove_cart_item(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'quantity' => 1,
        ]);

        $cart = new CartManager;
        $key = array_key_first($cart->getItems());

        $response = $this->delete(route('cart.remove'), [
            'key' => $key,
        ]);

        $response->assertSessionHas('success');
        $items = $cart->getItems();
        $this->assertArrayNotHasKey($key, $items);
    }

    public function test_remove_multiple_cart_items(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'quantity' => 1,
            'color_id' => 1,
            'size_id' => 0,
        ]);

        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'quantity' => 1,
            'color_id' => 2,
            'size_id' => 0,
        ]);

        $cart = new CartManager;
        $keys = array_keys($cart->getItems());

        $response = $this->delete(route('cart.removeMultiple'), [
            'keys' => $keys,
        ]);

        $response->assertSessionHas('success');
        $this->assertEmpty($cart->getItems());
    }

    public function test_clear_cart(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'quantity' => 1,
        ]);

        $response = $this->post(route('cart.clear'));

        $response->assertSessionHas('success');
        $this->assertEmpty((new CartManager)->getItems());
    }

    public function test_cart_index_returns_view(): void
    {
        $response = $this->get(route('cart'));

        $response->assertOk();
        $response->assertViewIs('cart');
    }

    public function test_adding_same_product_merges_quantity(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'quantity' => 3,
        ]);

        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'quantity' => 2,
        ]);

        $cart = new CartManager;
        $items = $cart->getItems();
        $this->assertCount(1, $items);
        $item = reset($items);
        $this->assertEquals(5, $item['quantity']);
    }
}
