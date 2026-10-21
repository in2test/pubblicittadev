<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]));
});

it('lists the brands', function () {
    $brand = Brand::factory()->create(['name' => 'Clique']);

    Livewire::test(ListBrands::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$brand]);
});

it('renders the brand create form', function () {
    Livewire::test(CreateBrand::class)
        ->assertOk()
        ->assertFormExists();
});

it('creates a brand', function () {
    Livewire::test(CreateBrand::class)
        ->fillForm([
            'name' => 'ProJob',
            'slug' => 'projob',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Brand::where('name', 'ProJob')->exists())->toBeTrue();
});

it('renders the brand edit form', function () {
    $brand = Brand::factory()->create(['name' => 'Clique']);

    Livewire::test(EditBrand::class, ['record' => $brand->getRouteKey()])
        ->assertOk()
        ->assertFormExists();
});

it('updates a brand', function () {
    $brand = Brand::factory()->create(['name' => 'Clique']);

    Livewire::test(EditBrand::class, ['record' => $brand->getRouteKey()])
        ->fillForm(['name' => 'Clique Updated'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($brand->fresh()->name)->toBe('Clique Updated');
});
