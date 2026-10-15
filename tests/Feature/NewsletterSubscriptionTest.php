<?php

use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;

it('renders the newsletter component successfully', function () {
    get('/contact')->assertStatus(200);
});

it('can subscribe to the newsletter', function () {
    Livewire::test('newsletter-form')
        ->set('email', 'test@example.com')
        ->set('consent', true)
        ->call('subscribe')
        ->assertHasNoErrors()
        ->assertSet('subscribed', true);

    assertDatabaseHas('newsletter_subscriptions', [
        'email' => 'test@example.com',
        'is_active' => true,
    ]);
});
