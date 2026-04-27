<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Product;
use App\Services\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cart = new CartManager;
        $this->cart->clear();
    }

    protected CartManager $cart;

    public function test_adds_item_to_empty_cart(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $this->cart->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'price' => 50,
            'quantity' => 2,
        ]);

        $items = $this->cart->getItems();
        $this->assertCount(1, $items);
    }

    public function test_adds_same_product_merges_quantity(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $this->cart->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'price' => 50,
            'quantity' => 3,
        ]);

        $this->cart->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'price' => 50,
            'quantity' => 2,
        ]);

        $items = $this->cart->getItems();
        $this->assertCount(1, $items);
        $item = reset($items);
        $this->assertEquals(5, $item['quantity']);
    }

    public function test_adds_same_product_different_color_creates_new_line(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $this->cart->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'price' => 50,
            'quantity' => 1,
            'color_id' => 1,
        ]);

        $this->cart->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'price' => 50,
            'quantity' => 1,
            'color_id' => 2,
        ]);

        $items = $this->cart->getItems();
        $this->assertCount(2, $items);
    }

    public function test_updates_existing_item_quantity(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $this->cart->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'price' => 50,
            'quantity' => 2,
        ]);

        $key = array_key_first($this->cart->getItems());
        $this->cart->update($key, 5);

        $items = $this->cart->getItems();
        $this->assertEquals(5, $items[$key]['quantity']);
    }

    public function test_update_to_zero_removes_item(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $this->cart->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'price' => 50,
            'quantity' => 2,
        ]);

        $key = array_key_first($this->cart->getItems());
        $this->cart->update($key, 0);

        $items = $this->cart->getItems();
        $this->assertArrayNotHasKey($key, $items);
    }

    public function test_removes_single_item(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $this->cart->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'price' => 50,
            'quantity' => 1,
        ]);

        $key = array_key_first($this->cart->getItems());
        $this->cart->remove($key);

        $items = $this->cart->getItems();
        $this->assertArrayNotHasKey($key, $items);
    }

    public function test_removes_multiple_items(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $this->cart->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'price' => 50,
            'quantity' => 1,
            'color_id' => 1,
        ]);

        $this->cart->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'price' => 50,
            'quantity' => 1,
            'color_id' => 2,
        ]);

        $keys = array_keys($this->cart->getItems());
        $this->cart->removeMultiple($keys);

        $this->assertEmpty($this->cart->getItems());
    }

    public function test_clears_entire_cart(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $this->cart->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'price' => 50,
            'quantity' => 1,
        ]);

        $this->cart->clear();

        $this->assertEmpty($this->cart->getItems());
    }

    public function test_counts_total_items(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $this->cart->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'price' => 50,
            'quantity' => 3,
        ]);

        $this->cart->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'price' => 50,
            'quantity' => 2,
            'color_id' => 1,
        ]);

        $this->assertEquals(5, $this->cart->count());
    }

    public function test_counts_zero_on_empty_cart(): void
    {
        $this->assertEquals(0, $this->cart->count());
    }

    public function test_generates_different_keys_for_different_print_placements(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $this->cart->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'price' => 50,
            'quantity' => 1,
            'print_placements' => [1],
        ]);

        $this->cart->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'price' => 50,
            'quantity' => 1,
            'print_placements' => [2],
        ]);

        $items = $this->cart->getItems();
        $this->assertCount(2, $items);
    }

    public function test_generates_same_key_for_same_print_placements(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $placements = [1, 2];

        $this->cart->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'price' => 50,
            'quantity' => 1,
            'print_placements' => $placements,
        ]);

        $this->cart->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'price' => 50,
            'quantity' => 1,
            'print_placements' => $placements,
        ]);

        $items = $this->cart->getItems();
        $this->assertCount(1, $items);
    }
}
