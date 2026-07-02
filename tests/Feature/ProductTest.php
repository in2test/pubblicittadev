<?php

use App\Models\Product;
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
