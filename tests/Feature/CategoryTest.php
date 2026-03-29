<?php

use App\Models\Category;
use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('deletes attached image from storage when a category is deleted', function () {
    Storage::fake('public');

    $category = new Category;
    $category->name = 'Test Category';
    $category->slug = 'test-category';
    $category->save();

    $imageFile = UploadedFile::fake()->image('category-image.jpg');
    $path = $imageFile->store('category_images', 'public');

    $image = new Image;
    $image->image_path = $path;
    $image->image_url = Storage::disk('public')->url($path);
    $image->category_id = $category->id;
    $image->save();

    expect(Storage::disk('public')->exists($path))->toBeTrue();
    expect(Image::count())->toBe(1);

    // Delete
    $category->delete();

    // Verify
    expect(Image::count())->toBe(0);
    expect(Storage::disk('public')->exists($path))->toBeFalse();
});
