<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SyncLevel;
use App\Enums\SyncStatus;
use App\Filament\Resources\Products\NewWaveProducts\Pages\EditNewWaveProduct;
use App\Filament\Resources\Products\NewWaveProducts\Pages\ListNewWaveProducts;
use App\Jobs\SyncNewWaveProductJob;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\NwgApiClient;
use App\Services\ProductAvailabilityService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Mockery;

uses(RefreshDatabase::class);

beforeEach(function () {
    test()->actingAs(User::factory()->create(['role' => 'admin']));
});

it('syncs product metadata without touching variations when the meta level is used', function () {
    $product = Product::factory()->create([
        'sku' => 'META-LEVEL-SKU',
        'type' => Product::TYPE_NEWWAVE,
        'name' => 'Old name',
        'price' => 10,
    ]);

    $mockClient = Mockery::mock(NwgApiClient::class);
    $mockClient->shouldReceive('getProductMetadata')
        ->once()
        ->with('META-LEVEL-SKU')
        ->andReturn([
            'productName' => 'Fresh name',
            'productCatalogText' => 'Fresh description',
            'retailPrice' => ['price' => 42.50],
            'productFabrics' => '100% cotone',
        ]);
    $mockClient->shouldNotReceive('getFullProductData');
    $mockClient->shouldNotReceive('getProductAvailability');
    $this->app->instance(NwgApiClient::class, $mockClient);

    app(ProductAvailabilityService::class)->syncByLevel($product, SyncLevel::Meta);

    $product->refresh();

    expect($product->name)->toBe('Fresh name')
        ->and((float) $product->price)->toBe(42.50)
        ->and($product->description)->toBe('Fresh description')
        ->and($product->technical_specs)->toBe('100% cotone')
        ->and($product->skus)->toHaveCount(0);
});

it('syncs only quantities when the availability level is used', function () {
    $product = Product::factory()->create([
        'sku' => 'AVAILABILITY-LEVEL-SKU',
        'type' => Product::TYPE_NEWWAVE,
        'name' => 'Untouched name',
    ]);

    $product->skus()->create([
        'sku' => 'AVAILABILITY-LEVEL-SKU-M',
        'quantity' => 1,
        'is_available' => true,
    ]);

    $mockClient = Mockery::mock(NwgApiClient::class);
    $mockClient->shouldReceive('getProductAvailability')
        ->once()
        ->with('AVAILABILITY-LEVEL-SKU')
        ->andReturn([
            'variations' => [[
                'skus' => [[
                    'sku' => 'AVAILABILITY-LEVEL-SKU-M',
                    'availability' => 8,
                ]],
            ]],
        ]);
    $mockClient->shouldNotReceive('getFullProductData');
    $mockClient->shouldNotReceive('getProductMetadata');
    $this->app->instance(NwgApiClient::class, $mockClient);

    app(ProductAvailabilityService::class)->syncByLevel($product, SyncLevel::Availability);

    $product->refresh();

    expect($product->skus()->first()->quantity)->toBe(4)
        ->and($product->name)->toBe('Untouched name');
});

it('syncs the full catalogue when the full level is used', function () {
    $product = Product::factory()->create([
        'sku' => 'FULL-LEVEL-SKU',
        'type' => Product::TYPE_NEWWAVE,
    ]);

    $mockClient = Mockery::mock(NwgApiClient::class);
    $mockClient->shouldReceive('getFullProductData')
        ->once()
        ->with('FULL-LEVEL-SKU')
        ->andReturn([
            'productName' => 'Full sync name',
            'retailPrice' => ['price' => 30],
            'variations' => [
                [
                    'itemColorCode' => '10',
                    'itemWebColor' => 'Blue',
                    'skus' => [
                        [
                            'sku' => 'FULL-LEVEL-SKU-10-M',
                            'availability' => 20,
                            'skuSize' => ['size' => 'M', 'webtext' => 'Medium'],
                            'active' => true,
                        ],
                    ],
                ],
            ],
        ]);
    $mockClient->shouldNotReceive('getProductMetadata');
    $mockClient->shouldNotReceive('getProductAvailability');
    $this->app->instance(NwgApiClient::class, $mockClient);

    app(ProductAvailabilityService::class)->syncByLevel($product, SyncLevel::Full);

    $product->refresh();

    expect($product->name)->toBe('Full sync name')
        ->and($product->skus)->toHaveCount(1)
        ->and($product->skus->first()->quantity)->toBe(10);
});

it('runs the job at the requested sync level', function () {
    $product = Product::factory()->create([
        'sku' => 'JOB-LEVEL-SKU',
        'type' => Product::TYPE_NEWWAVE,
    ]);

    $mockClient = Mockery::mock(NwgApiClient::class);
    $mockClient->shouldReceive('getProductMetadata')
        ->once()
        ->with('JOB-LEVEL-SKU')
        ->andReturn(['productName' => 'Job meta name']);
    $mockClient->shouldNotReceive('getFullProductData');
    $mockClient->shouldNotReceive('getProductAvailability');
    $this->app->instance(NwgApiClient::class, $mockClient);

    (new SyncNewWaveProductJob($product->id, SyncLevel::Meta))
        ->handle(app(ProductAvailabilityService::class));

    $product->refresh();

    expect($product->name)->toBe('Job meta name')
        ->and($product->sync_status)->toBe(SyncStatus::Synced)
        ->and($product->sync_progress)->toBe(100)
        ->and($product->synced_at)->not->toBeNull();
});

it('resolves the product when the job is given a model instance', function () {
    $product = Product::factory()->create([
        'sku' => 'JOB-MODEL-SKU',
        'type' => Product::TYPE_NEWWAVE,
        'name' => 'Model dispatch name',
    ]);

    $mockClient = Mockery::mock(NwgApiClient::class);
    $mockClient->shouldReceive('getProductMetadata')
        ->once()
        ->with('JOB-MODEL-SKU')
        ->andReturn(['productName' => 'Resolved from model']);
    $this->app->instance(NwgApiClient::class, $mockClient);

    (new SyncNewWaveProductJob($product, SyncLevel::Meta))
        ->handle(app(ProductAvailabilityService::class));

    expect($product->refresh()->name)->toBe('Resolved from model');
});

it('dispatches the sync job with the selected level from the NewWave table', function () {
    Bus::fake();

    $product = Product::factory()->create(['type' => Product::TYPE_NEWWAVE]);

    Livewire::test(ListNewWaveProducts::class)
        ->selectTableRecords([$product->getKey()])
        ->callAction(TestAction::make('synchronize')->table()->bulk(), data: [
            'level' => SyncLevel::Availability->value,
        ])
        ->assertHasNoFormErrors();

    Bus::assertDispatched(
        SyncNewWaveProductJob::class,
        fn (SyncNewWaveProductJob $job): bool => $job->productId === $product->id
            && $job->level === SyncLevel::Availability,
    );
});

it('offers the sync level choice on the admin product page', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'type' => Product::TYPE_NEWWAVE,
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get(route('product', ['category' => $category->slug, 'product' => $product->slug]));

    $response->assertOk()
        ->assertSee('name="level"', escape: false)
        ->assertSee(SyncLevel::Full->value, escape: false)
        ->assertSee(SyncLevel::Meta->value, escape: false)
        ->assertSee(SyncLevel::Availability->value, escape: false);
});

it('dispatches the sync job with the selected level from the NewWave edit page', function () {
    Bus::fake();

    $product = Product::factory()->create(['type' => Product::TYPE_NEWWAVE]);

    Livewire::test(EditNewWaveProduct::class, ['record' => $product->getRouteKey()])
        ->callAction('syncFromApi', data: [
            'level' => SyncLevel::Meta->value,
        ])
        ->assertHasNoFormErrors();

    Bus::assertDispatched(
        SyncNewWaveProductJob::class,
        fn (SyncNewWaveProductJob $job): bool => $job->productId === $product->id
            && $job->level === SyncLevel::Meta,
    );
});
