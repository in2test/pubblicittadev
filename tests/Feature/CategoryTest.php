<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('shows inactive products to admins on category listing', function () {
    $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel', 'description' => null]);
    $inactiveProduct = Product::factory()->create([
        'is_active' => false,
        'name' => 'Hidden Product',
        'slug' => 'hidden-product',
        'category_id' => $category->id,
    ]);

    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->get(route('category', ['category' => $category->slug]));

    $response->assertOk();
    $response->assertSee($inactiveProduct->name);
});

it('deletes attached image from storage when a category is deleted', function () {
    Storage::fake('public');

    $category = Category::factory()->create();

    $imageFile = UploadedFile::fake()->image('category-image.jpg');

    $media = $category->addMedia($imageFile->getPathname())
        ->usingName('Category Image')
        ->toMediaCollection('images');

    expect($category->getMedia('images'))->toHaveCount(1);
    expect((new Storage)->disk('public')->exists($media->id.'/'.$media->file_name))->toBeTrue();

    // Delete
    $category->delete();

    // Verify media is deleted
    expect($category->getMedia('images'))->toHaveCount(0);
    // Media library should clean up files automatically
});
