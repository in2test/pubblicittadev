<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\VariationTypes\Pages\CreateVariationType;
use App\Filament\Resources\VariationTypes\Pages\EditVariationType;
use App\Filament\Resources\VariationTypes\Pages\ListVariationTypes;
use App\Models\User;
use App\Models\VariationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    test()->actingAs(User::factory()->create(['role' => 'admin']));
});

it('can render variation types list page', function () {
    Livewire::test(ListVariationTypes::class)
        ->assertStatus(200);
});

it('can create a variation type with options', function () {
    Livewire::test(CreateVariationType::class)
        ->fillForm([
            'name' => 'Colore Maglietta',
            'presentation_type' => 'color_swatch',
            'allow_multiple' => false,
            'options' => [
                [
                    'name' => 'Rosso',
                    'color_hex' => '#ff0000',
                    'sort_order' => 1,
                    'default_modifier_type' => 'flat',
                    'default_price_modifier' => 0.00,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('variation_types', [
        'name' => 'Colore Maglietta',
        'presentation_type' => 'color_swatch',
    ]);

    $this->assertDatabaseHas('variation_options', [
        'name' => 'Rosso',
        'color_hex' => '#ff0000',
    ]);
});

it('can edit variation type details and options', function () {
    $variationType = VariationType::factory()->create([
        'name' => 'Taglia',
        'presentation_type' => 'select',
    ]);

    $option = $variationType->options()->create([
        'name' => 'M',
        'sort_order' => 1,
        'default_modifier_type' => 'flat',
        'default_price_modifier' => 0.00,
    ]);

    Livewire::test(EditVariationType::class, [
        'record' => $variationType->getKey(),
    ])
        ->assertSee("{$option->id} M")
        ->fillForm([
            'name' => 'Taglia Abbigliamento',
            'options' => [
                'record-'.$option->id => [
                    'name' => 'M - Medium',
                    'sort_order' => 2,
                    'default_modifier_type' => 'flat',
                    'default_price_modifier' => 1.50,
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('variation_types', [
        'id' => $variationType->id,
        'name' => 'Taglia Abbigliamento',
    ]);

    $this->assertDatabaseHas('variation_options', [
        'id' => $option->id,
        'name' => 'M - Medium',
        'default_price_modifier' => 1.50,
    ]);
});

it('shows option values in item labels when the value field is hidden', function () {
    $variationType = VariationType::factory()->create([
        'name' => 'Colore',
        'presentation_type' => 'color_swatch',
    ]);

    $option = $variationType->options()->create([
        'name' => 'Bianco',
        'value' => '00',
        'sort_order' => 1,
        'default_modifier_type' => 'flat',
        'default_price_modifier' => 0.00,
    ]);
    $otherOption = $variationType->options()->create([
        'name' => 'Beige',
        'value' => '02',
        'color_hex' => '#f5f5dc',
        'sort_order' => 2,
        'default_modifier_type' => 'flat',
        'default_price_modifier' => 0.00,
    ]);

    Livewire::test(EditVariationType::class, [
        'record' => $variationType->getKey(),
    ])
        ->fillForm([
            'options' => [
                'record-'.$option->id => [
                    'name' => 'Bianco modificato',
                    'color_hex' => null,
                    'sort_order' => 1,
                    'default_modifier_type' => 'flat',
                    'default_price_modifier' => 0.00,
                ],
                'record-'.$otherOption->id => [
                    'name' => 'Beige',
                    'color_hex' => '#f5f5dc',
                    'sort_order' => 2,
                    'default_modifier_type' => 'flat',
                    'default_price_modifier' => 0.00,
                ],
            ],
        ])
        ->assertSee('00 Bianco')
        ->assertSee('02 Beige')
        ->assertDontSee(" {$option->id} Bianco ")
        ->assertDontSee(" {$otherOption->id} Beige ")
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('variation_options', [
        'id' => $option->id,
        'name' => 'Bianco modificato',
        'color_hex' => null,
    ]);
});
