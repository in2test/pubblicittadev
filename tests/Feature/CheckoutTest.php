<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\OrderPlacedNotification;
use App\Models\Address;
use App\Models\Product;
use App\Models\User;
use App\Services\CartManager;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;
use Mockery;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    protected CartManager $cartManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cartManager = app(CartManager::class);
        $this->cartManager->clear();
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);
        parent::tearDown();
    }

    public function test_redirects_to_stripe_checkout_session_creates_pending_order_and_sends_emails(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['price' => 10.00]);

        $this->cartManager->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'quantity' => 2,
            'price' => 10.00,
        ]);

        // Mock Stripe's HTTP Client to intercept the API call made by Session::create()
        $mockHttpClient = Mockery::mock(ClientInterface::class);
        $mockHttpClient->shouldReceive('request')
            ->once()
            ->andReturn([
                json_encode([
                    'id' => 'sess_test',
                    'object' => 'checkout.session',
                    'url' => 'https://checkout.stripe.com/test',
                ]),
                200,
                [],
            ]);
        ApiRequestor::setHttpClient($mockHttpClient);

        $address = Address::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('checkout.session'), [
                'shipping_address_id' => $address->id,
                'billing_address_id' => $address->id,
            ])
            ->assertRedirect('https://checkout.stripe.com/test');

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'payment_status' => 'pending',
            'work_status' => 'pending',
            'total_price' => 20.00,
            'stripe_session_id' => 'sess_test',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 10.00,
            'subtotal' => 20.00,
        ]);

        Mail::assertSent(OrderPlacedNotification::class, fn ($mail) => $mail->hasTo($user->email));
        Mail::assertSent(OrderPlacedNotification::class, fn ($mail) => $mail->hasTo($admin->email));
    }

    public function test_prevents_checkout_with_empty_cart(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('checkout.session'))
            ->assertRedirect(route('cart'))
            ->assertSessionHas('error', 'Il tuo carrello è vuoto.');
    }

    public function test_clears_cart_on_success_page(): void
    {
        $user = User::factory()->create();

        $this->cartManager->add([
            'product_id' => 1,
            'product_name' => 'Test',
            'quantity' => 1,
        ]);

        $this->assertNotEmpty($this->cartManager->getItems());

        $this->actingAs($user)
            ->get(route('checkout.success'))
            ->assertStatus(200);

        $this->assertEmpty($this->cartManager->getItems());
    }

    public function test_renders_checkout_page_successfully(): void
    {
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
        $this->app->instance(CartManager::class, $mockCartManager);

        $this->actingAs($user);

        Volt::test('pages.checkout')
            ->assertSee('Checkout')
            ->assertSee($address->name)
            ->assertHasNoErrors();
    }

    public function test_quotation_flow_creates_quotation_order_clears_cart_and_redirects_to_success(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['price' => 10.00]);

        $this->cartManager->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'quantity' => 2,
            'price' => 10.00,
        ]);

        $address = Address::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('checkout.session'), [
                'shipping_address_id' => $address->id,
                'billing_address_id' => $address->id,
                'payment_method' => 'quotation',
            ])
            ->assertRedirect(route('checkout.success'));

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'payment_status' => 'quotation',
            'work_status' => 'pending',
            'total_price' => 20.00,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 10.00,
            'subtotal' => 20.00,
        ]);

        $this->assertEmpty($this->cartManager->getItems());

        Mail::assertSent(OrderPlacedNotification::class, fn ($mail) => $mail->hasTo($user->email));
        Mail::assertSent(OrderPlacedNotification::class, fn ($mail) => $mail->hasTo($admin->email));
    }

    public function test_direct_quotation_flow_from_cart_creates_order_and_redirects_to_success(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['price' => 10.00]);

        $this->cartManager->add([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'quantity' => 3,
            'price' => 10.00,
        ]);

        $this->actingAs($user)
            ->post(route('checkout.quotation'))
            ->assertRedirect(route('checkout.success'));

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'payment_status' => 'quotation',
            'work_status' => 'pending',
            'total_price' => 30.00,
            'shipping_address_id' => null,
            'billing_address_id' => null,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 10.00,
            'subtotal' => 30.00,
        ]);

        $this->assertEmpty($this->cartManager->getItems());

        Mail::assertSent(OrderPlacedNotification::class, fn ($mail) => $mail->hasTo($user->email));
        Mail::assertSent(OrderPlacedNotification::class, fn ($mail) => $mail->hasTo($admin->email));
    }
}
