<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    if (method_exists($this, 'skipUnlessFortifyFeature')) {
        $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());
    } else {
        $this->markTestSkipped('Fortify testing helpers not available.');
    }
});

test('two factor challenge redirects to login when not authenticated', function () {
    $response = $this->get(route('two-factor.login'));

    $response->assertRedirect(route('login'));
});

test('two factor challenge can be rendered', function () {
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));
});
