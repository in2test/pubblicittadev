<?php

use App\Models\Address;
use App\Models\Order;
use App\Models\User;

test('stripe webhook endpoint returns 400 when signature is missing', function () {
    $response = $this->postJson('/webhooks/stripe', []);

    $response->assertStatus(400);
});

test('stripe webhook handles checkout.session.completed and marks order as paid', function () {
    $user = User::factory()->create();
    $shippingAddress = Address::factory()->create([
        'user_id' => $user->id,
        'type' => 'shipping',
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'shipping_address_id' => $shippingAddress->id,
        'billing_address_id' => $shippingAddress->id,
        'payment_status' => 'pending',
        'total_price' => 100.00,
    ]);

    $payloadData = [
        'id' => 'evt_test_123',
        'object' => 'event',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_123',
                'payment_intent' => 'pi_test_123',
                'metadata' => [
                    'order_id' => $order->id,
                ],
            ],
        ],
    ];

    $payload = json_encode($payloadData);
    $timestamp = time();
    $secret = 'whsec_test';
    config(['stripe.webhook_secret' => $secret]);

    $signedPayload = $timestamp.'.'.$payload;
    $signature = hash_hmac('sha256', $signedPayload, $secret);
    $sigHeader = "t={$timestamp},v1={$signature}";

    $response = $this->postJson('/webhooks/stripe', $payloadData, [
        'Stripe-Signature' => $sigHeader,
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'success']);

    $order->refresh();
    expect($order->payment_status)->toBe('paid');
    expect($order->stripe_payment_intent_id)->toBe('pi_test_123');
});
