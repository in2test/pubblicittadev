<?php

use App\Filament\Resources\Colors\Pages\CreateColor;
use App\Filament\Resources\Colors\Pages\EditColor;
use App\Filament\Resources\Colors\Pages\ListColors;
use App\Models\Color;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can render the color list page', function () {
    Livewire::test(ListColors::class)
        ->assertSuccessful();
});

it('can list colors', function () {
    $colors = Color::factory()->count(5)->create();

    Livewire::test(ListColors::class)
        ->assertCanSeeTableRecords($colors);
});

it('can render create color page', function () {
    Livewire::test(CreateColor::class)
        ->assertSuccessful();
});

it('can create a color', function () {
    $newData = Color::factory()->make();

    Livewire::test(CreateColor::class)
        ->fillForm([
            'color_name' => $newData->color_name,
            'color_hex' => $newData->color_hex,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Color::where('color_name', $newData->color_name)->exists())->toBeTrue();
});

it('can render edit color page', function () {
    $color = Color::factory()->create();

    Livewire::test(EditColor::class, [
        'record' => $color->getRouteKey(),
    ])
        ->assertSuccessful();
});

it('can edit a color', function () {
    $color = Color::factory()->create();
    $newData = Color::factory()->make();

    Livewire::test(EditColor::class, [
        'record' => $color->getRouteKey(),
    ])
        ->fillForm([
            'color_name' => $newData->color_name,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($color->refresh()->color_name)->toBe($newData->color_name);
});

it('can delete a color', function () {
    $color = Color::factory()->create();

    Livewire::test(EditColor::class, [
        'record' => $color->getRouteKey(),
    ])
        ->callAction('delete');

    expect(Color::find($color->id))->toBeNull();
});
