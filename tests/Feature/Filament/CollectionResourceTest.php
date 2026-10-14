<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Collections\Pages\CreateCollection;
use App\Filament\Resources\Collections\Pages\EditCollection;
use App\Filament\Resources\Collections\Pages\ListCollections;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

beforeEach(function () {
    test()->actingAs(User::factory()->create(['role' => 'admin']));
});

it('can render collection list page', function () {
    Livewire::test(ListCollections::class)
        ->assertStatus(200);
});

it('can list collections in table', function () {
    $collections = Collection::factory()->count(3)->create();

    Livewire::test(ListCollections::class)
        ->assertCanSeeTableRecords($collections);
});

it('can create a collection with products and featured flag', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $category->id]);

    Livewire::test(CreateCollection::class)
        ->fillForm([
            'name' => 'Apertura Nuova Attività',
            'slug' => 'apertura-nuova-attivita',
            'description' => 'Biglietti da visita, volantini e pannelli per cominciare.',
            'is_featured' => true,
            'is_active' => true,
            'sort_order' => 1,
            'products' => [$product->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('collections', [
        'name' => 'Apertura Nuova Attività',
        'slug' => 'apertura-nuova-attivita',
        'is_featured' => true,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $collection = Collection::where('slug', 'apertura-nuova-attivita')->first();
    expect($collection->products)->toHaveCount(1)
        ->and($collection->products->first()->id)->toBe($product->id);
});

it('can edit a collection', function () {
    $collection = Collection::factory()->create([
        'name' => 'Old Bundle Name',
        'is_featured' => false,
    ]);

    Livewire::test(EditCollection::class, ['record' => $collection->getRouteKey()])
        ->fillForm([
            'name' => 'Updated Bundle Name',
            'is_featured' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas('collections', [
        'id' => $collection->id,
        'name' => 'Updated Bundle Name',
        'is_featured' => true,
    ]);
});
