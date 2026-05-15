<?php

use App\Models\Address;
use App\Models\Product;
use App\Models\User;
use App\Services\CartManager;
use Stripe\Checkout\Session;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->cartManager = app(CartManager::class);
    $this->cartManager->clear();
});

it('redirects to stripe checkout session and creates a pending order', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 10.00]);

    // Add item to cart
    $this->cartManager->add([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_slug' => $product->slug,
        'quantity' => 2,
        'price' => 10.00,
    ]);

    // Mock Stripe Session
    // We use a partial mock or a class-level alias for Stripe classes
    $mockSession = Mockery::mock('alias:Stripe\Checkout\Session');
    $mockSession->shouldReceive('create')
        ->once()
        ->andReturn((object) ['url' => 'https://checkout.stripe.com/test', 'id' => 'sess_test']);

    $address = Address::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->post(route('checkout.session'), [
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
        ])
        ->assertRedirect('https://checkout.stripe.com/test');

    assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'status' => 'pending',
        'total_price' => 20.00,
        'stripe_session_id' => 'sess_test',
    ]);

    assertDatabaseHas('order_items', [
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 10.00,
        'subtotal' => 20.00,
    ]);
});

it('prevents checkout with an empty cart', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('checkout.session'))
        ->assertRedirect(route('cart'))
        ->assertSessionHas('error', 'Il tuo carrello è vuoto.');
});

it('clears cart on success page', function () {
    $user = User::factory()->create();
    $this->cartManager->add([
        'product_id' => 1,
        'product_name' => 'Test',
        'quantity' => 1,
    ]);

    expect($this->cartManager->getItems())->not->toBeEmpty();

    actingAs($user)
        ->get(route('checkout.success'))
        ->assertStatus(200);

    expect($this->cartManager->getItems())->toBeEmpty();
});
