<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

test('can view collections index page with active collections', function () {
    $category = Category::factory()->create();
    $activeCollection = Collection::factory()->create([
        'name' => 'Apertura Attività',
        'is_active' => true,
    ]);
    $inactiveCollection = Collection::factory()->inactive()->create([
        'name' => 'Archived Bundle',
    ]);

    $product = Product::factory()->create(['category_id' => $category->id, 'is_active' => true]);
    $activeCollection->products()->attach($product->id);

    $response = get(route('collections.index'));

    $response->assertOk();
    $response->assertSee('Apertura Attività');
    $response->assertDontSee('Archived Bundle');
});

test('can view collection show page and see associated products', function () {
    $category = Category::factory()->create();
    $collection = Collection::factory()->create([
        'name' => 'Golfing Kit',
        'slug' => 'golfing-kit',
        'description' => 'Kit completo con polo tecnica, pantaloni e cappellino.',
        'is_active' => true,
    ]);

    $product1 = Product::factory()->create(['name' => 'Active Polo Sport', 'category_id' => $category->id, 'is_active' => true]);
    $product2 = Product::factory()->create(['name' => 'Cargo Pants Golf', 'category_id' => $category->id, 'is_active' => true]);
    $collection->products()->attach([$product1->id, $product2->id]);

    $response = get(route('collections.show', $collection->slug));

    $response->assertOk();
    $response->assertSee('Golfing Kit');
    $response->assertSee('Kit completo con polo tecnica, pantaloni e cappellino.');
    $response->assertSee('Active Polo Sport');
    $response->assertSee('Cargo Pants Golf');
});

test('inactive collection returns 404 for guests', function () {
    $collection = Collection::factory()->inactive()->create([
        'slug' => 'inactive-bundle',
    ]);

    $response = get(route('collections.show', $collection->slug));

    $response->assertNotFound();
});

test('homepage includes featured collections in view data and hero slides', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $category->id, 'is_active' => true]);

    $featuredCollection = Collection::factory()->featured()->create([
        'name' => 'Kit Apertura Ristorante',
        'is_active' => true,
    ]);
    $featuredCollection->products()->attach($product->id);

    $response = get(route('home'));

    $response->assertOk();
    $response->assertViewHas('collections');
    $response->assertSee('Kit Apertura Ristorante');
});

test('products and collections have many-to-many relationship', function () {
    $category = Category::factory()->create();
    $collection1 = Collection::factory()->create(['name' => 'Bundle 1']);
    $collection2 = Collection::factory()->create(['name' => 'Bundle 2']);

    $product = Product::factory()->create(['category_id' => $category->id]);

    $collection1->products()->attach($product->id, ['sort_order' => 1]);
    $collection2->products()->attach($product->id, ['sort_order' => 2]);

    expect($product->collections)->toHaveCount(2)
        ->and($collection1->products)->toHaveCount(1)
        ->and($collection1->products->first()->id)->toBe($product->id);
});
