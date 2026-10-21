<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SyncLevel;
use App\Jobs\SyncNewWaveProductJob;
use App\Models\Category;
use App\Models\Product;
use App\Services\GoogleMerchantFeedCache;
use App\Services\NwgApiClient;
use App\Services\ProductAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Cache;
use Mockery;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::store((string) config('services.google.feed_cache_store'))->flush();
});

it('serves the feed from the cache when it has been built', function () {
    $category = Category::factory()->create();

    Product::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'sku' => 'CACHED-1',
        'name' => 'Cached Product',
    ]);

    $this->artisan('feed:google-merchant --force')->assertSuccessful();

    $response = $this->get('/feed/google-merchant.xml');

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/xml; charset=utf-8')
        ->assertSee('CACHED-1', escape: false);
});

it('renders the feed on the first request when the cache is empty', function () {
    $category = Category::factory()->create();

    Product::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'sku' => 'COLD-1',
    ]);

    $response = $this->get('/feed/google-merchant.xml');

    $response->assertOk()->assertSee('COLD-1', escape: false);

    expect(app(GoogleMerchantFeedCache::class)->isFresh())->toBeTrue();
});

it('serves the gzipped feed when the crawler accepts it', function () {
    $category = Category::factory()->create();

    Product::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'sku' => 'GZIP-1',
    ]);

    $response = $this->get('/feed/google-merchant.xml', [
        'Accept-Encoding' => 'gzip',
    ]);

    $response->assertOk()
        ->assertHeader('Content-Encoding', 'gzip');

    $decoded = gzdecode($response->getContent());

    expect($decoded)->toContain('GZIP-1');
});

it('serves the plain feed when the crawler does not accept gzip', function () {
    $category = Category::factory()->create();

    Product::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'sku' => 'PLAIN-1',
    ]);

    $response = $this->get('/feed/google-merchant.xml', [
        'Accept-Encoding' => 'identity',
    ]);

    $response->assertOk()
        ->assertHeaderMissing('Content-Encoding')
        ->assertSee('PLAIN-1', escape: false);
});

it('skips the rebuild when a fresh feed is already cached', function () {
    $this->artisan('feed:google-merchant --force')->assertSuccessful();

    $this->artisan('feed:google-merchant')
        ->expectsOutputToContain('already cached')
        ->assertSuccessful();
});

it('rebuilds the feed when forced', function () {
    $this->artisan('feed:google-merchant --force')->assertSuccessful();

    $this->artisan('feed:google-merchant --force')
        ->expectsOutputToContain('Building the Google Merchant feed')
        ->assertSuccessful();
});

it('drops the cached feed so the next request renders it again', function () {
    $this->artisan('feed:google-merchant --force')->assertSuccessful();

    $cache = app(GoogleMerchantFeedCache::class);

    expect($cache->isFresh())->toBeTrue();

    $cache->forget();

    expect($cache->isFresh())->toBeFalse();
});

it('drops the cached feed when a product changes', function () {
    $category = Category::factory()->create();

    $product = Product::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'sku' => 'STALE-1',
    ]);

    $this->artisan('feed:google-merchant --force')->assertSuccessful();

    expect(app(GoogleMerchantFeedCache::class)->isFresh())->toBeTrue();

    $product->update(['name' => 'Renamed Product']);

    expect(app(GoogleMerchantFeedCache::class)->isFresh())->toBeFalse();
});

it('drops the cached feed when a product is deleted', function () {
    $category = Category::factory()->create();

    $product = Product::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'sku' => 'DELETED-1',
    ]);

    $this->artisan('feed:google-merchant --force')->assertSuccessful();

    $product->delete();

    expect(app(GoogleMerchantFeedCache::class)->isFresh())->toBeFalse();
});

it('rebuilds the feed after the request that changed the catalogue is over', function () {
    $category = Category::factory()->create();

    $product = Product::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'sku' => 'DEFERRED-1',
    ]);

    $this->artisan('feed:google-merchant --force')->assertSuccessful();

    $cache = app(GoogleMerchantFeedCache::class);

    $product->update(['name' => 'Deferred Product']);

    // The rebuild is deferred, so it has not run yet
    expect($cache->isFresh())->toBeFalse();

    $this->app->make(DeferredCallbackCollection::class)->invoke();

    expect($cache->isFresh())->toBeTrue();
});

it('serves the rebuilt feed with the new product data', function () {
    $category = Category::factory()->create();

    $product = Product::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'sku' => 'REBUILT-1',
        'name' => 'Original Name',
    ]);

    $this->artisan('feed:google-merchant --force')->assertSuccessful();

    $product->update(['name' => 'Updated Name']);

    $this->app->make(DeferredCallbackCollection::class)->invoke();

    $response = $this->get('/feed/google-merchant.xml');

    $response->assertOk()
        ->assertSee('Updated Name', escape: false)
        ->assertDontSee('Original Name', escape: false);
});

it('rebuilds the feed only once for a burst of changes', function () {
    $category = Category::factory()->create();

    $product = Product::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'sku' => 'BURST-1',
    ]);

    $this->artisan('feed:google-merchant --force')->assertSuccessful();

    $product->update(['name' => 'One']);
    $product->update(['name' => 'Two']);
    $product->update(['name' => 'Three']);

    $deferred = $this->app->make(DeferredCallbackCollection::class);

    expect($deferred->count())->toBe(1);

    $deferred->invoke();

    expect(app(GoogleMerchantFeedCache::class)->isFresh())->toBeTrue();
});

it('rebuilds the feed after a NewWave synchronisation', function () {
    $category = Category::factory()->create();

    $product = Product::factory()->create([
        'type' => Product::TYPE_NEWWAVE,
        'is_active' => true,
        'category_id' => $category->id,
        'sku' => 'SYNCED-1',
    ]);

    $this->artisan('feed:google-merchant --force')->assertSuccessful();

    $cache = app(GoogleMerchantFeedCache::class);

    expect($cache->isFresh())->toBeTrue();

    $mockClient = Mockery::mock(NwgApiClient::class);
    $mockClient->shouldReceive('getProductMetadata')
        ->once()
        ->with('SYNCED-1')
        ->andReturn([
            'productName' => 'Synced Name',
            'productBrand' => 'Clique',
        ]);
    $this->app->instance(NwgApiClient::class, $mockClient);

    app(ProductAvailabilityService::class)->syncByLevel($product, SyncLevel::Meta);

    // The rebuild is deferred, so it has not run yet
    expect($cache->isFresh())->toBeFalse();

    $this->app->make(DeferredCallbackCollection::class)->invoke();

    expect($cache->isFresh())->toBeTrue();

    $this->get('/feed/google-merchant.xml')
        ->assertOk()
        ->assertSee('Synced Name', escape: false);
});

it('rebuilds the feed when a queue worker runs the synchronisation', function () {
    $category = Category::factory()->create();

    $product = Product::factory()->create([
        'type' => Product::TYPE_NEWWAVE,
        'is_active' => true,
        'category_id' => $category->id,
        'sku' => 'WORKER-1',
    ]);

    $this->artisan('feed:google-merchant --force')->assertSuccessful();

    $cache = app(GoogleMerchantFeedCache::class);

    expect($cache->isFresh())->toBeTrue();

    $mockClient = Mockery::mock(NwgApiClient::class);
    $mockClient->shouldReceive('getProductMetadata')
        ->once()
        ->with('WORKER-1')
        ->andReturn([
            'productName' => 'Worker Synced Name',
            'productBrand' => 'Clique',
        ]);
    $this->app->instance(NwgApiClient::class, $mockClient);

    // The sync runs on a queue, so the deferred rebuild has to survive the
    // worker: the "sync" connection is skipped by Laravel, the database one
    // is the one the server uses.
    config()->set('queue.default', 'database');

    SyncNewWaveProductJob::dispatch($product->id, SyncLevel::Meta);

    $this->artisan('queue:work', [
        '--once' => true,
        '--stop-when-empty' => true,
    ])->assertExitCode(0);

    expect($cache->isFresh())->toBeTrue();
});
