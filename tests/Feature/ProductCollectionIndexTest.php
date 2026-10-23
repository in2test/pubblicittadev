<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Collection;
use App\Models\PricingTier;
use App\Models\Product;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

test('product page lists the other products of the collection it belongs to', function () {
    $category = Category::factory()->create();
    $collection = Collection::factory()->create([
        'name' => 'Golfing Kit',
        'slug' => 'golfing-kit',
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'name' => 'Active Polo Sport',
        'category_id' => $category->id,
        'is_active' => true,
    ]);
    $sibling = Product::factory()->create([
        'name' => 'Cargo Pants Golf',
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $collection->products()->attach([$product->id, $sibling->id]);

    $response = get(route('product', ['category' => $category->slug, 'product' => $product->slug]));

    $response->assertOk();
    $response->assertSee('Prodotti Della Stessa Collezione');
    $response->assertSee('Cargo Pants Golf');
    $response->assertSee('Golfing Kit');

    $collections = $response->viewData('relatedCollections');
    expect($collections)->toHaveCount(1)
        ->and($collections->first()->products->pluck('id')->all())->toBe([$sibling->id]);
});

test('product page groups the listed products by collection when the product belongs to more than one', function () {
    $category = Category::factory()->create();
    $golfingKit = Collection::factory()->create([
        'name' => 'Golfing Kit',
        'slug' => 'golfing-kit',
        'is_active' => true,
    ]);
    $workoutKit = Collection::factory()->create([
        'name' => 'Workout Kit',
        'slug' => 'workout-kit',
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'name' => 'Active Polo Sport',
        'category_id' => $category->id,
        'is_active' => true,
    ]);
    $golfingSibling = Product::factory()->create([
        'name' => 'Cargo Pants Golf',
        'category_id' => $category->id,
        'is_active' => true,
    ]);
    $workoutSibling = Product::factory()->create([
        'name' => 'Running Cap',
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $golfingKit->products()->attach([$product->id, $golfingSibling->id]);
    $workoutKit->products()->attach([$product->id, $workoutSibling->id]);

    $response = get(route('product', ['category' => $category->slug, 'product' => $product->slug]));

    $response->assertOk();
    $response->assertSee('Prodotti Delle Tue Collezioni');
    $response->assertSee('Golfing Kit');
    $response->assertSee('Workout Kit');
    $response->assertSee('Cargo Pants Golf');
    $response->assertSee('Running Cap');

    $collections = $response->viewData('relatedCollections');
    expect($collections)->toHaveCount(2)
        ->and($collections->pluck('name')->all())->toBe(['Golfing Kit', 'Workout Kit'])
        ->and($collections->first()->products->pluck('id')->all())->toBe([$golfingSibling->id])
        ->and($collections->last()->products->pluck('id')->all())->toBe([$workoutSibling->id]);
});

test('product page does not list a collection index when the product has no collection', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'name' => 'Active Polo Sport',
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $response = get(route('product', ['category' => $category->slug, 'product' => $product->slug]));

    $response->assertOk();
    $response->assertDontSee('Prodotti Della Stessa Collezione');
    $response->assertDontSee('Prodotti Delle Tue Collezioni');

    expect($response->viewData('relatedCollections'))->toBeEmpty();
});

test('product page does not list inactive products of its collection', function () {
    $category = Category::factory()->create();
    $collection = Collection::factory()->create([
        'name' => 'Golfing Kit',
        'slug' => 'golfing-kit',
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'name' => 'Active Polo Sport',
        'category_id' => $category->id,
        'is_active' => true,
    ]);
    $activeSibling = Product::factory()->create([
        'name' => 'Cargo Pants Golf',
        'category_id' => $category->id,
        'is_active' => true,
    ]);
    $inactiveSibling = Product::factory()->create([
        'name' => 'Polo Capi Spentini',
        'category_id' => $category->id,
        'is_active' => false,
    ]);

    $collection->products()->attach([$product->id, $activeSibling->id, $inactiveSibling->id]);

    $response = get(route('product', ['category' => $category->slug, 'product' => $product->slug]));

    $response->assertOk();
    $response->assertSee('Cargo Pants Golf');
    $response->assertDontSee('Polo Capi Spentini');

    $collections = $response->viewData('relatedCollections');
    expect($collections->first()->products->pluck('id')->all())->toBe([$activeSibling->id]);
});

test('product page does not list products of an inactive collection', function () {
    $category = Category::factory()->create();
    $collection = Collection::factory()->inactive()->create([
        'name' => 'Archivio Kit',
        'slug' => 'archivio-kit',
    ]);

    $product = Product::factory()->create([
        'name' => 'Active Polo Sport',
        'category_id' => $category->id,
        'is_active' => true,
    ]);
    $sibling = Product::factory()->create([
        'name' => 'Cargo Pants Golf',
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $collection->products()->attach([$product->id, $sibling->id]);

    $response = get(route('product', ['category' => $category->slug, 'product' => $product->slug]));

    $response->assertOk();
    $response->assertDontSee('Prodotti Della Stessa Collezione');
    $response->assertDontSee('Archivio Kit');

    expect($response->viewData('relatedCollections'))->toBeEmpty();
});

test('sibling products are priced with the tier that matches their quantity range', function () {
    $category = Category::factory()->create();
    $collection = Collection::factory()->create([
        'name' => 'Golfing Kit',
        'slug' => 'golfing-kit',
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'name' => 'Active Polo Sport',
        'category_id' => $category->id,
        'is_active' => true,
    ]);
    $sibling = Product::factory()->create([
        'name' => 'Cargo Pants Golf',
        'category_id' => $category->id,
        'is_active' => true,
        'price' => 100,
    ]);

    // The open ended tier is the cheapest one, so it only wins while the
    // capped tier does not match the quantity.
    PricingTier::create([
        'product_id' => $sibling->id,
        'min_quantity' => 1,
        'max_quantity' => null,
        'price_per_unit' => 5,
    ]);
    PricingTier::create([
        'product_id' => $sibling->id,
        'min_quantity' => 50,
        'max_quantity' => 99,
        'price_per_unit' => 10,
    ]);

    $collection->products()->attach([$product->id, $sibling->id]);

    $response = get(route('product', ['category' => $category->slug, 'product' => $product->slug]));

    $response->assertOk();

    $listedSibling = $response->viewData('relatedCollections')->first()->products->first();

    expect($listedSibling->pricingTiers->pluck('max_quantity')->all())->toContain(99)
        ->and($listedSibling->getTierPrice(150))->toBe(5.0)
        ->and($listedSibling->getTierPrice(60))->toBe(10.0);
});

test('the collection index lists the siblings in the order of the collection', function () {
    $category = Category::factory()->create();
    $collection = Collection::factory()->create([
        'name' => 'Golfing Kit',
        'slug' => 'golfing-kit',
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'name' => 'Active Polo Sport',
        'category_id' => $category->id,
        'is_active' => true,
    ]);
    $first = Product::factory()->create([
        'name' => 'Cargo Pants Golf',
        'category_id' => $category->id,
        'is_active' => true,
    ]);
    $second = Product::factory()->create([
        'name' => 'Running Cap',
        'category_id' => $category->id,
        'is_active' => true,
    ]);
    $third = Product::factory()->create([
        'name' => 'Golf Gloves',
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    // The manual order of the collection wins over the creation order, and the
    // siblings sharing the same position are listed by product id and not by
    // the order they happen to sit in the pivot table.
    $collection->products()->attach([
        $product->id => ['sort_order' => 0],
        $first->id => ['sort_order' => 5],
        $third->id => ['sort_order' => 1],
        $second->id => ['sort_order' => 1],
    ]);

    $response = get(route('product', ['category' => $category->slug, 'product' => $product->slug]));

    $response->assertOk();

    $collections = $response->viewData('relatedCollections');

    expect($collections->first()->products->pluck('id')->all())
        ->toBe([$second->id, $third->id, $first->id]);
});

test('the collection index lists at most eight products per collection', function () {
    $category = Category::factory()->create();
    $collection = Collection::factory()->create([
        'name' => 'Golfing Kit',
        'slug' => 'golfing-kit',
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'name' => 'Active Polo Sport',
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $siblings = Product::factory()->count(10)->create([
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $collection->products()->attach([$product->id, ...$siblings->pluck('id')->all()]);

    $response = get(route('product', ['category' => $category->slug, 'product' => $product->slug]));

    $response->assertOk();

    $listed = $response->viewData('relatedCollections')->first();

    expect($listed->products)->toHaveCount(8)
        ->and($listed->getAttribute('siblings_count'))->toBe(10);
});

test('the eight products limit is applied to each collection on its own', function () {
    $category = Category::factory()->create();
    $golfingKit = Collection::factory()->create([
        'name' => 'Golfing Kit',
        'slug' => 'golfing-kit',
        'is_active' => true,
    ]);
    $workoutKit = Collection::factory()->create([
        'name' => 'Workout Kit',
        'slug' => 'workout-kit',
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'name' => 'Active Polo Sport',
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $golfingSiblings = Product::factory()->count(10)->create([
        'category_id' => $category->id,
        'is_active' => true,
    ]);
    $workoutSiblings = Product::factory()->count(10)->create([
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $golfingKit->products()->attach([$product->id, ...$golfingSiblings->pluck('id')->all()]);
    $workoutKit->products()->attach([$product->id, ...$workoutSiblings->pluck('id')->all()]);

    $response = get(route('product', ['category' => $category->slug, 'product' => $product->slug]));

    $response->assertOk();

    $collections = $response->viewData('relatedCollections');

    expect($collections)->toHaveCount(2)
        ->and($collections->first()->products)->toHaveCount(8)
        ->and($collections->last()->products)->toHaveCount(8)
        ->and($collections->first()->getAttribute('siblings_count'))->toBe(10)
        ->and($collections->last()->getAttribute('siblings_count'))->toBe(10);
});

test('the collection index does not query the database once per sibling product', function () {
    $category = Category::factory()->create();
    $collection = Collection::factory()->create([
        'name' => 'Golfing Kit',
        'slug' => 'golfing-kit',
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'name' => 'Active Polo Sport',
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $siblings = Product::factory()->count(6)->create([
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $collection->products()->attach([$product->id, ...$siblings->pluck('id')->all()]);

    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = strtolower(ltrim($query->sql));
    });

    $response = get(route('product', ['category' => $category->slug, 'product' => $product->slug]));

    $response->assertOk();

    // The siblings are loaded with a constant number of queries: the relations
    // the product card reads are eager loaded, so the count does not grow
    // with the number of listed products.
    $siblingQueries = array_filter(
        $queries,
        fn (string $sql): bool => str_contains($sql, 'product_skus')
            || str_contains($sql, 'pricing_tiers')
            || str_contains($sql, 'from `media`')
            || str_contains($sql, 'from `images`'),
    );

    expect(count($siblingQueries))->toBeLessThan(20);
});
