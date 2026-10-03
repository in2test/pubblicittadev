<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Products\NewWaveProducts\Pages\EditNewWaveProduct;
use App\Filament\Resources\Products\NewWaveProducts\Pages\ListNewWaveProducts;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Schemas\NewWaveProductForm;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Models\Product;
use App\Models\User;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    test()->actingAs(User::factory()->create(['role' => 'admin']));
});

it('can render newwave product list page', function () {
    Livewire::test(ListNewWaveProducts::class)
        ->assertStatus(200);
});

it('can render the standard product creation wizard', function () {
    Livewire::test(CreateProduct::class)
        ->assertStatus(200);
});

it('composes the NewWave product schema into its tabs', function () {
    $schema = NewWaveProductForm::configure(Schema::make());

    expect($schema->getComponents())
        ->toHaveCount(1)
        ->and($schema->getComponents()[0])
        ->toBeInstanceOf(Tabs::class);
});

it('composes the standard product schema into its tabs', function () {
    $schema = ProductForm::configure(Schema::make());

    expect($schema->getComponents())
        ->toHaveCount(2)
        ->and($schema->getComponents()[1])
        ->toBeInstanceOf(Tabs::class);
});

it('can render newwave product edit page', function () {
    $product = Product::factory()->create(['type' => 'newwave']);

    Livewire::test(EditNewWaveProduct::class, [
        'record' => $product->getRouteKey(),
    ])
        ->assertStatus(200);
});

it('can list newwave products', function () {
    $products = Product::factory()->count(5)->create(['type' => 'newwave']);

    Livewire::test(ListNewWaveProducts::class)
        ->assertCanSeeTableRecords($products);
});

it('can search newwave products by name', function () {
    $product = Product::factory()->create(['name' => 'Specific Name', 'type' => 'newwave']);
    $otherProduct = Product::factory()->create(['name' => 'Other Name', 'type' => 'newwave']);

    Livewire::test(ListNewWaveProducts::class)
        ->searchTable('Specific Name')
        ->assertCanSeeTableRecords([$product])
        ->assertCanNotSeeTableRecords([$otherProduct]);
});
