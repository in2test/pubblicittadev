<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('shows user orders on dashboard', function () {
    $user = User::factory()->create();
    Order::factory()->count(3)->create(['user_id' => $user->id]);
    Order::factory()->create(); // Other user's order

    actingAs($user)
        ->get(route('dashboard.orders'))
        ->assertStatus(200)
        ->assertViewHas('orders', fn ($orders) => $orders->count() === 3);
});

it('shows order details for owned order', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);
    $order->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => 10.00,
        'subtotal' => 10.00,
    ]);

    actingAs($user)
        ->get(route('dashboard.orders.show', $order))
        ->assertStatus(200)
        ->assertSee($order->order_number)
        ->assertSee($product->name);
});

it('prevents viewing other users order details', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $otherUser->id]);

    actingAs($user)
        ->get(route('dashboard.orders.show', $order))
        ->assertStatus(403);
});
