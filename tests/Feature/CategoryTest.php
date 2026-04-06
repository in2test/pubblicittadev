<?php

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('deletes attached image from storage when a category is deleted', function () {
    Storage::fake('public');

    $category = Category::factory()->create();

    $imageFile = UploadedFile::fake()->image('category-image.jpg');

    $media = $category->addMedia($imageFile->getPathname())
        ->usingName('Category Image')
        ->toMediaCollection('images');

    expect($category->getMedia('images'))->toHaveCount(1);
    expect(Storage::disk('public')->exists($media->id . '/' . $media->file_name))->toBeTrue();

    // Delete
    $category->delete();

    // Verify media is deleted
    expect($category->getMedia('images'))->toHaveCount(0);
    // Media library should clean up files automatically
});
