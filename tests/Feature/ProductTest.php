<?php

use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('deletes attached images from storage when a product is deleted', function () {
    Storage::fake('public');

    $category = new Category;
    $category->name = 'Test Category';
    $category->slug = 'test-category';
    $category->save();

    $product = new Product;
    $product->name = 'Test Product';
    $product->slug = 'test-product';
    $product->description = 'Test Description';
    $product->price = 10.0;
    $product->category_id = $category->id;
    $product->save();

    $imageFile = UploadedFile::fake()->image('test-image.jpg');
    $path = $imageFile->store('product_images', 'public');

    $image = new Image;
    $image->image_path = $path;
    $image->image_url = Storage::disk('public')->url($path);
    $image->product_id = $product->id;
    $image->category_id = $category->id;
    $image->save();

    expect(Storage::disk('public')->exists($path))->toBeTrue();
    expect(Image::count())->toBe(1);

    // Delete the product
    $product->delete();

    // Verify
    expect(Image::count())->toBe(0);
    expect(Storage::disk('public')->exists($path))->toBeFalse();
});
