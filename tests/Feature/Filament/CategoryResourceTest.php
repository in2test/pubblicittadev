<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

beforeEach(function () {
    test()->actingAs(User::factory()->create(['role' => 'admin']));
});

it('can render category list page', function () {
    Livewire::test(ListCategories::class)
        ->assertStatus(200);
});

it('can list categories', function () {
    $categories = Category::factory()->count(3)->create();

    Livewire::test(ListCategories::class)
        ->assertCanSeeTableRecords($categories);
});

it('can create a category', function () {
    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name' => 'New Category',
            'slug' => 'new-category',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('categories', [
        'name' => 'New Category',
        'slug' => 'new-category',
    ]);
});
