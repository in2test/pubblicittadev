<?php

declare(strict_types=1);

use App\Models\Address;
use App\Models\Order;
use App\Models\User;

test('renders google customer reviews badge on pages with main layout', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('merchantWidgetScript', false);
    $response->assertSee('merchantwidget.start', false);
    $response->assertSee('5807637378', false);
    $response->assertSee('BOTTOM_RIGHT', false);
    $response->assertSee('IT', false);
});

test('renders google customer reviews opt-in on checkout success with order data', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create([
        'user_id' => $user->id,
        'country' => 'IT',
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'order_number' => 'ORD-TEST-12345',
        'shipping_address_id' => $address->id,
        'billing_address_id' => $address->id,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['order_id' => $order->id])
        ->get(route('checkout.success'));

    $response->assertStatus(200);
    $response->assertSee('renderOptIn', false);
    $response->assertSee('surveyoptin', false);
    $response->assertSee('5807637378', false);
    $response->assertSee('ORD-TEST-12345', false);
    $response->assertSee($user->email, false);
    $response->assertSee('"delivery_country": "IT"', false);
    $response->assertSee('"estimated_delivery_date":', false);
});

test('resolves order by stripe session id on checkout success and renders opt-in', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create([
        'user_id' => $user->id,
        'country' => 'IT',
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'order_number' => 'ORD-STRIPE-999',
        'stripe_session_id' => 'sess_stripe_mock_123',
        'shipping_address_id' => $address->id,
        'billing_address_id' => $address->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('checkout.success', ['session_id' => 'sess_stripe_mock_123']));

    $response->assertStatus(200);
    $response->assertSee('ORD-STRIPE-999', false);
    $response->assertSee('renderOptIn', false);
    $response->assertSee('5807637378', false);
});
