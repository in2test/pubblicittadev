<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\Products\StandardProducts\Pages\CreateStandardProduct;
use App\Filament\Resources\Products\StandardProducts\Pages\EditStandardProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(RefreshDatabase::class);

beforeEach(function () {
    test()->actingAs(User::factory()->create(['role' => 'admin']));
});

it('can create standard product without images', function () {
    $category = Category::factory()->create();

    Livewire::test(CreateStandardProduct::class)
        ->fillForm([
            'name' => 'Standard Product Test',
            'slug' => 'standard-product-test',
            'price' => 10.00,
            'category_id' => $category->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::where('slug', 'standard-product-test')->first();
    expect($product)->not->toBeNull();

    // Verify that NO media record was created since no images were uploaded
    expect($product->media()->count())->toBe(0);
});

it('can render edit page for standard product without images', function () {
    $product = Product::factory()->create([
        'type' => 'standard',
    ]);

    Livewire::test(EditStandardProduct::class, [
        'record' => $product->getRouteKey(),
    ])
        ->assertStatus(200);
});

it('can edit standard product with existing image and update custom properties without breaking manipulations', function () {
    $product = Product::factory()->create([
        'type' => 'standard',
    ]);

    // Create a media record manually to bypass GD image requirements in tests
    $media = new Media;
    $media->model_type = Product::class;
    $media->model_id = $product->id;
    $media->uuid = (string) Str::uuid();
    $media->collection_name = 'images';
    $media->name = 'test-image';
    $media->file_name = 'test-image.jpg';
    $media->disk = 'public';
    $media->conversions_disk = 'public';
    $media->size = 1234;
    $media->manipulations = [];
    $media->custom_properties = [
        'color_ids' => [],
        'alt' => 'Original Alt',
        'is_manual' => false,
    ];
    $media->generated_conversions = [];
    $media->responsive_images = [];
    $media->save();

    // Verify it is saved correctly first
    $media->refresh();
    expect($media->manipulations)->toBeArray();
    expect($media->manipulations)->toBeEmpty();

    $stateKey = 'record-'.$media->id;

    // Render the edit page and update the form using the relationship record key
    Livewire::test(EditStandardProduct::class, [
        'record' => $product->getRouteKey(),
    ])
        ->fillForm([
            'media' => [
                $stateKey => [
                    'custom_properties' => [
                        'color_ids' => [],
                        'alt' => 'Updated Alt',
                        'is_manual' => true,
                    ],
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Assert that the media properties were updated, but manipulations was not double-serialized
    $media->refresh();
    expect($media->custom_properties['alt'])->toBe('Updated Alt');
    expect($media->custom_properties['is_manual'])->toBeTrue();

    // Crucial check: manipulations must still be a clean PHP array!
    expect($media->manipulations)->toBeArray();
    expect($media->manipulations)->toBeEmpty();

    // Also query the raw database column to ensure it is not stored as double-serialized string '"[]"'
    $rawManipulations = DB::table('media')
        ->where('id', $media->id)
        ->value('manipulations');
    expect($rawManipulations)->toBe('[]');
});
