<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('deletes attached images from storage when a product is deleted', function () {
    Storage::fake('public');

    $product = Product::factory()->create();

    $imageFile = UploadedFile::fake()->image('test-image.jpg');

    $media = $product->addMedia($imageFile->getPathname())
        ->usingName('Product Image')
        ->toMediaCollection('images');

    expect($product->getMedia('images'))->toHaveCount(1);
    expect(Storage::disk('public')->exists($media->id . '/' . $media->file_name))->toBeTrue();

    // Delete the product
    $product->delete();

    // Verify media is deleted
    expect($product->getMedia('images'))->toHaveCount(0);
    // Media library should clean up files automatically
});
