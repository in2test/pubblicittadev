<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProductClass;
use App\Filament\Resources\Products\ApparelProducts\Pages\CreateApparelProduct;
use App\Filament\Resources\Products\ApparelProducts\Pages\EditApparelProduct;
use App\Filament\Resources\Products\ApparelProducts\Pages\ListApparelProducts;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

// Uses RefreshDatabase trait to ensure the database is reset before each test
uses(RefreshDatabase::class);

// Set up the environment before each test runs
beforeEach(function () {
    // Authenticate as an admin user to access Filament panel
    test()->actingAs(User::factory()->create(['role' => 'admin']));
});

/**
 * Test: Create Apparel Product Without Images
 * Validates that we can successfully create a new apparel product via the Filament form.
 */
it('can create apparel product without images', function () {
    // Create a parent category first since it's required for the product
    $category = Category::factory()->create();

    // Simulate the Livewire component for creating an apparel product
    Livewire::test(CreateApparelProduct::class)
        ->fillForm([
            'name' => 'Apparel Product Test', // Product name
            'slug' => 'apparel-product-test', // URL slug
            'price' => 10.00, // Base price
            'category_id' => $category->id, // Associated category
        ])
        ->call('create') // Trigger the create action
        ->assertHasNoFormErrors(); // Ensure validation passes

    // Check if the product was actually saved in the database
    $product = Product::where('slug', 'apparel-product-test')->first();
    expect($product)->not->toBeNull();

    // Verify that NO media record was created since no images were uploaded
    expect($product->media()->count())->toBe(0);
});

/**
 * Test: Render Edit Page
 * Ensures that the edit page for an apparel product loads successfully (Status 200).
 */
it('can render edit page for apparel product without images', function () {
    // Generate a dummy apparel product using the factory
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
    ]);

    // Test the edit component rendering
    Livewire::test(EditApparelProduct::class, [
        'record' => $product->getRouteKey(),
    ])
        ->assertStatus(200); // 200 OK means the page loaded without exceptions
});

/**
 * Test: Edit Apparel Product Media Properties
 * This is a critical test to ensure that updating custom properties on a media item
 * does not break the `manipulations` array by double-serializing it.
 */
it('can edit apparel product with existing image and update custom properties without breaking manipulations', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
    ]);

    // Create a media record manually to bypass GD image processing requirements in tests
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
    Livewire::test(EditApparelProduct::class, [
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

/**
 * Test: List Apparel Products
 * Ensures that the list page renders and displays the created products.
 */
it('can list apparel products', function () {
    // Create multiple apparel products
    $products = Product::factory()->count(3)->create([
        'product_class' => ProductClass::Apparel,
    ]);

    Livewire::test(ListApparelProducts::class)
        ->assertCanSeeTableRecords($products)
        ->assertStatus(200);
});

/**
 * Test: Delete Apparel Product
 * Ensures that an admin can delete an apparel product from the list page.
 */
it('can delete apparel product', function () {
    $product = Product::factory()->create([
        'product_class' => ProductClass::Apparel,
    ]);

    Livewire::test(ListApparelProducts::class)
        ->assertTableActionVisible('delete', $product)
        ->callTableAction('delete', $product)
        ->assertHasNoTableActionErrors();

    // Verify the product is removed from the database
    expect(Product::find($product->id))->toBeNull();
});
