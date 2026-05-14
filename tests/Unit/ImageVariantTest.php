<?php

use App\Models\Image;
use App\Models\Product;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('uploaded image generates thumbnail medium and large webp variants', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('product.jpg', 1200, 900);
    $path = $file->storeAs('product_images', 'product.jpg', 'public');

    $image = Image::create([
        'image_path' => $path,
        'image_description' => 'Variant test',
        'product_id' => Product::factory()->create()->id,
    ]);

    $image->refresh();

    expect($image->thumbnail_path)->toBeString()->not()->toBeEmpty();
    expect($image->medium_path)->toBeString()->not()->toBeEmpty();
    expect($image->large_path)->toBeString()->not()->toBeEmpty();

    /** @var FilesystemAdapter $disk */
    $disk = Storage::disk('public');
    $disk->assertExists($image->thumbnail_path);
    $disk->assertExists($image->medium_path);
    $disk->assertExists($image->large_path);

    $manager = ImageManager::usingDriver(GdDriver::class);
    $thumbnail = $manager->decodePath(Storage::disk('public')->path($image->thumbnail_path));
    $medium = $manager->decodePath(Storage::disk('public')->path($image->medium_path));
    $large = $manager->decodePath(Storage::disk('public')->path($image->large_path));

    expect($thumbnail->width())->toBeLessThanOrEqual(150);
    expect($thumbnail->height())->toBeLessThanOrEqual(150);
    expect($medium->width())->toBeLessThanOrEqual(600);
    expect($medium->height())->toBeLessThanOrEqual(600);
    expect($large->width())->toBeLessThanOrEqual(1000);
    expect($large->height())->toBeLessThanOrEqual(1000);
});
