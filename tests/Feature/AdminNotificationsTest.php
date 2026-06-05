<?php

declare(strict_types=1);

use App\Mail\NewsletterSubscribedAdminNotification;
use App\Mail\UserCreatedAdminNotification;
use App\Models\NewsletterSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('creating a user sends an email notification to active admins only', function () {
    Mail::fake();

    // Create admins
    $activeAdmin1 = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
    $activeAdmin2 = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
    $inactiveAdmin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => false]);
    $client = User::factory()->create(['role' => User::ROLE_CLIENT, 'is_active' => true]);

    // Create a new user (which triggers the observer)
    $newUser = User::factory()->create([
        'name' => 'New Customer',
        'email' => 'newcustomer@example.com',
        'role' => User::ROLE_CLIENT,
    ]);

    // Assert that notification was sent to active admins
    Mail::assertSent(UserCreatedAdminNotification::class, fn ($mail) => $mail->hasTo($activeAdmin1->email) && $mail->user->id === $newUser->id);

    Mail::assertSent(UserCreatedAdminNotification::class, fn ($mail) => $mail->hasTo($activeAdmin2->email) && $mail->user->id === $newUser->id);

    // Assert it was not sent to inactive admin, client, or the new user themselves
    Mail::assertNotSent(UserCreatedAdminNotification::class, fn ($mail) => $mail->hasTo($inactiveAdmin->email)
        || $mail->hasTo($client->email)
        || $mail->hasTo($newUser->email));
});

test('creating a newsletter subscription sends an email notification to active admins', function () {
    Mail::fake();

    // Create admins
    $activeAdmin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
    $inactiveAdmin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => false]);
    $client = User::factory()->create(['role' => User::ROLE_CLIENT, 'is_active' => true]);

    // Create a new active newsletter subscription
    $subscription = NewsletterSubscription::create([
        'email' => 'newsletter@example.com',
        'is_active' => true,
    ]);

    // Assert email sent to active admin
    Mail::assertSent(NewsletterSubscribedAdminNotification::class, fn ($mail) => $mail->hasTo($activeAdmin->email) && $mail->subscription->id === $subscription->id);

    // Assert email not sent to inactive admin or client
    Mail::assertNotSent(NewsletterSubscribedAdminNotification::class, fn ($mail) => $mail->hasTo($inactiveAdmin->email) || $mail->hasTo($client->email));
});

test('creating an inactive newsletter subscription does not send email notifications', function () {
    Mail::fake();

    User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

    NewsletterSubscription::create([
        'email' => 'newsletter@example.com',
        'is_active' => false,
    ]);

    Mail::assertNotSent(NewsletterSubscribedAdminNotification::class);
});
