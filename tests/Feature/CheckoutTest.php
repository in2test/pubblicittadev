<?php

use App\Mail\OrderPlacedNotification;
use App\Models\Address;
use App\Models\Product;
use App\Models\User;
use App\Services\CartManager;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;
use Stripe\Checkout\Session;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->cartManager = app(CartManager::class);
    $this->cartManager->clear();
});

it('redirects to stripe checkout session, creates a pending order, and sends placement emails', function () {
    Mail::fake();

    $user = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
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
        'payment_status' => 'pending',
        'work_status' => 'pending',
        'total_price' => 20.00,
        'stripe_session_id' => 'sess_test',
    ]);

    assertDatabaseHas('order_items', [
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 10.00,
        'subtotal' => 20.00,
    ]);

    // Assert customer received OrderPlacedNotification
    Mail::assertSent(OrderPlacedNotification::class, fn ($mail) => $mail->hasTo($user->email));

    // Assert admin received OrderPlacedNotification
    Mail::assertSent(OrderPlacedNotification::class, fn ($mail) => $mail->hasTo($admin->email));
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

it('renders the checkout page successfully', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 10.00]);
    $address = Address::factory()->create(['user_id' => $user->id]);

    $mockCartManager = Mockery::mock(CartManager::class);
    $mockCartManager->shouldReceive('isEmpty')->andReturn(false);
    $mockCartManager->shouldReceive('total')->andReturn(10.00);
    $mockCartManager->shouldReceive('getItems')->andReturn([
        [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'quantity' => 1,
            'price' => 10.00,
        ],
    ]);
    app()->instance(CartManager::class, $mockCartManager);

    actingAs($user);

    Volt::test('pages.checkout')
        ->assertSee('Checkout')
        ->assertSee($address->name)
        ->assertHasNoErrors();
});
