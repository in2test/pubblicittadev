<?php

use App\Models\Product;
use App\Models\ProductVariationType;
use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('deletes attached images from storage when a product is deleted', function () {
    $disk = config('media-library.disk_name', 'public');
    Storage::fake($disk);

    $product = Product::factory()->create();

    $imageFile = UploadedFile::fake()->image('test-image.png');

    $media = $product->addMedia($imageFile->getPathname())
        ->usingName('Product Image')
        ->toMediaCollection('images');

    expect($product->getMedia('images'))->toHaveCount(1);
    expect(Storage::disk($disk)->exists($media->id.'/'.$media->file_name))->toBeTrue();

    // Delete the product
    $product->delete();

    // Verify media is deleted
    expect($product->getMedia('images'))->toHaveCount(0);
    expect(Storage::disk($disk)->exists($media->id.'/'.$media->file_name))->toBeFalse();
});

it('resolves material and pattern from their linked variation options', function () {
    $product = Product::factory()->create();

    foreach ([
        VariationType::MATERIALE => 'cotone',
        VariationType::MOTIVO => 'righe',
    ] as $typeName => $value) {
        $type = VariationType::factory()->create(['name' => $typeName]);
        $option = VariationOption::factory()->create([
            'variation_type_id' => $type->id,
            'value' => $value,
        ]);
        $pivot = ProductVariationType::create([
            'product_id' => $product->id,
            'variation_type_id' => $type->id,
        ]);

        $pivot->options()->create(['variation_option_id' => $option->id]);
    }

    $product->refresh();

    expect($product->material)->toBe('cotone')
        ->and($product->pattern)->toBe('righe');
});
