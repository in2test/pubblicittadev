<?php

use Laravel\Fortify\Features;

beforeEach(function () {
    if (method_exists($this, 'skipUnlessFortifyFeature')) {
        $this->skipUnlessFortifyFeature(Features::registration());
    } else {
        $this->markTestSkipped('Fortify testing helpers not available.');
    }
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});
