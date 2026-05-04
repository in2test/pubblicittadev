<?php

use App\Enums\SyncStatus;
use App\Filament\Resources\Products\NewWaveProducts\Pages\EditNewWaveProduct;
use App\Models\Image;
use App\Models\Product;
use App\Models\User;
use App\Services\NwgApiClient;
use App\Services\ProductSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\MediaLibrary\Downloaders\Downloader;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

class TestDownloader implements Downloader
{
    public function getTempFile(string $url): string
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'media-library');

        $image = imagecreatetruecolor(1, 1);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
        imagepng($image, $temporaryFile);
        imagedestroy($image);

        return $temporaryFile;
    }
}

class FakeNwgApiClient extends NwgApiClient
{
    public function __construct() {}

    public function getFullProductData(string $productNumber): ?array
    {
        return [
            'productName' => 'Test Product',
            'productCatalogText' => 'Test description',
            'retailPrice' => ['price' => 100],
            'pictures' => [],
            'variations' => [
                [
                    'itemColorCode' => '99',
                    'itemWebColor' => 'Blue',
                    'pictures' => [
                        [
                            'standardUrl' => 'https://example.com/variation-99.jpg',
                            'thumbnailUrl' => 'https://example.com/variation-99-thumb.jpg',
                            'largeThumbnailUrl' => 'https://example.com/variation-99-large.jpg',
                        ],
                    ],
                    'skus' => [
                        [
                            'availability' => 10,
                            'sku' => 'TEST-99-M',
                            'skuSize' => [
                                'webtext' => 'M',
                                'size' => 'M',
                            ],
                            'active' => true,
                        ],
                    ],
                ],
            ],
        ];
    }
}

it('persists remote image urls in the image model from the NewWave product form', function () {
    $product = Product::factory()->create([
        'type' => Product::TYPE_NEWWAVE,
        'sync_status' => SyncStatus::Synced,
    ]);

    Livewire::test(EditNewWaveProduct::class, [
        'record' => $product->getRouteKey(),
    ])
        ->fillForm([
            'remote_images' => [
                [
                    'image_url' => 'https://example.com/test-image.jpg',
                    'image_description' => 'Test remote image',
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Image::where('product_id', $product->id)
        ->where('image_url', 'https://example.com/test-image.jpg')
        ->exists())->toBeTrue();
});

it('assigns a remote variation image to its color when syncing NewWave data', function () {
    $product = Product::factory()->create([
        'sku' => 'TESTSKU',
        'type' => Product::TYPE_NEWWAVE,
        'sync_status' => SyncStatus::Pending,
    ]);

    $synchronizer = new ProductSynchronizer(new FakeNwgApiClient);
    $synchronizer->syncProduct($product);

    $image = Image::where('product_id', $product->id)
        ->where('image_url', 'https://example.com/variation-99.jpg')
        ->first();

    expect($image)->not->toBeNull();
    expect($image->color_id)->not->toBeNull();
    expect($image->color->color_code)->toBe('99');
});

it('can download a remote image url into the product media library', function () {
    Storage::fake('public');
    config(['media-library.media_downloader' => TestDownloader::class]);

    $product = Product::factory()->create([
        'type' => Product::TYPE_NEWWAVE,
        'sync_status' => SyncStatus::Synced,
    ]);

    $image = Image::create([
        'product_id' => $product->id,
        'image_url' => 'https://example.com/test-image.png',
        'image_description' => 'Remote test image',
    ]);

    $media = $image->downloadToMediaLibrary();

    expect($media)->not->toBeNull();
    expect($product->getMedia('images')->count())->toBe(1);
});

it('skips the remote image from the combined image gallery once it is downloaded locally', function () {
    Storage::fake('public');

    $product = Product::factory()->create([
        'type' => Product::TYPE_NEWWAVE,
        'remote_images' => [
            [
                'id' => 'remote-1',
                'url' => 'https://example.com/test-image.png',
                'thumb' => 'https://example.com/test-image-thumb.png',
                'medium' => 'https://example.com/test-image-medium.png',
                'large' => 'https://example.com/test-image.png',
                'color_ids' => [],
            ],
        ],
    ]);

    Storage::disk('public')->makeDirectory('temp');

    $temporaryPath = Storage::disk('public')->path('temp/test-image.png');
    $imageResource = imagecreatetruecolor(1, 1);
    imagesavealpha($imageResource, true);
    $transparent = imagecolorallocatealpha($imageResource, 0, 0, 0, 127);
    imagefill($imageResource, 0, 0, $transparent);
    imagepng($imageResource, $temporaryPath);
    imagedestroy($imageResource);

    $media = $product
        ->addMedia(Storage::disk('public')->path('temp/test-image.png'))
        ->usingName('Downloaded remote')
        ->withCustomProperties([
            'remote_resource_url' => [
                'standard' => 'https://example.com/test-image.png',
            ],
        ])
        ->toMediaCollection('images');

    $product->syncLocalMediaToImageRecords();

    $images = $product->getAllImages();

    expect($images->count())->toBe(1);
    expect($images->first()->is_remote)->toBeFalse();
    expect($images->first()->id)->toBe((string) $media->id);
});

it('removes the image record when a downloaded local image is deleted', function () {
    Storage::fake('public');

    $product = Product::factory()->create([
        'type' => Product::TYPE_NEWWAVE,
        'remote_images' => [
            [
                'id' => 'remote-1',
                'url' => 'https://example.com/test-image.png',
                'thumb' => 'https://example.com/test-image-thumb.png',
                'medium' => 'https://example.com/test-image-medium.png',
                'large' => 'https://example.com/test-image.png',
                'color_ids' => [],
            ],
        ],
    ]);

    Storage::disk('public')->makeDirectory('temp');
    $temporaryPath = Storage::disk('public')->path('temp/test-image.png');
    $imageResource = imagecreatetruecolor(1, 1);
    imagesavealpha($imageResource, true);
    $transparent = imagecolorallocatealpha($imageResource, 0, 0, 0, 127);
    imagefill($imageResource, 0, 0, $transparent);
    imagepng($imageResource, $temporaryPath);
    imagedestroy($imageResource);

    $media = $product
        ->addMedia($temporaryPath)
        ->usingName('Downloaded remote')
        ->withCustomProperties([
            'remote_resource_url' => [
                'standard' => 'https://example.com/test-image.png',
            ],
        ])
        ->toMediaCollection('images');

    $product->syncLocalMediaToImageRecords();

    expect(Image::where('product_id', $product->id)
        ->where('image_url', 'https://example.com/test-image.png')
        ->exists())->toBeTrue();

    $media->delete();

    expect(Image::where('product_id', $product->id)
        ->where('image_url', 'https://example.com/test-image.png')
        ->exists())->toBeFalse();
});
