<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    test()->actingAs(User::factory()->create(['role' => 'admin']));
});

it('can render user list page', function () {
    Livewire::test(ListUsers::class)
        ->assertStatus(200);
});

it('can list users', function () {
    $users = User::factory()->count(3)->create();

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords($users);
});

it('can search users by email', function () {
    $user = User::factory()->create(['email' => 'findme@example.com']);

    Livewire::test(ListUsers::class)
        ->searchTable('findme@example.com')
        ->assertCanSeeTableRecords([$user]);
});

it('can see email verified status', function () {
    $verifiedUser = User::factory()->create(['email_verified_at' => now()]);
    $unverifiedUser = User::factory()->create(['email_verified_at' => null]);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$verifiedUser, $unverifiedUser]);
});

it('can verify a user email manually via action', function () {
    $unverifiedUser = User::factory()->create(['email_verified_at' => null]);

    expect($unverifiedUser->hasVerifiedEmail())->toBeFalse();

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('verify')->table($unverifiedUser));

    expect($unverifiedUser->refresh()->hasVerifiedEmail())->toBeTrue();
});
