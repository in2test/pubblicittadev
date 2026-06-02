<?php

use Livewire\Volt\Volt;

it('renders the newsletter component successfully', function () {
    $this->get('/contact')->assertStatus(200);
});

it('can subscribe to the newsletter', function () {
    Volt::test('newsletter-form')
        ->set('email', 'test@example.com')
        ->set('consent', true)
        ->call('subscribe')
        ->assertHasNoErrors()
        ->assertSet('subscribed', true);

    $this->assertDatabaseHas('newsletter_subscriptions', [
        'email' => 'test@example.com',
        'is_active' => true,
    ]);
});
