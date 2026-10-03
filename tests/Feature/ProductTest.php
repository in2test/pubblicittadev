<?php

use App\Models\Image;
use App\Models\Product;
use App\Models\ProductVariationType;
use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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

it('delegates product variation display data while preserving its results', function () {
    $product = Product::factory()->create();
    $colorType = VariationType::factory()->create([
        'name' => 'Colore',
        'presentation_type' => 'color_swatch',
    ]);
    $productVariationType = ProductVariationType::create([
        'product_id' => $product->id,
        'variation_type_id' => $colorType->id,
        'has_images' => true,
        'is_modifier' => false,
        'sort_order' => 0,
    ]);

    $whiteOption = VariationOption::factory()->create([
        'variation_type_id' => $colorType->id,
        'name' => 'Bianco',
        'value' => 'white',
        'sort_order' => 0,
    ]);
    $navyOption = VariationOption::factory()->create([
        'variation_type_id' => $colorType->id,
        'name' => 'Navy',
        'value' => 'navy',
        'sort_order' => 1,
    ]);

    $productVariationType->options()->create(['variation_option_id' => $whiteOption->id]);
    $productVariationType->options()->create(['variation_option_id' => $navyOption->id]);

    $previewColors = $product->getPreviewColors(1);

    expect($product->getColorOptions())->toBe('Bianco, Navy')
        ->and($previewColors['display']->pluck('name')->all())->toBe(['Bianco'])
        ->and($previewColors['remaining'])->toBe(1)
        ->and($previewColors['total'])->toBe(2);

    $product->load('productVariationTypes.options.option');
    $loadedPreviewColors = $product->getPreviewColors(1);

    expect($loadedPreviewColors['display']->pluck('name')->all())->toBe(['Bianco'])
        ->and($loadedPreviewColors['remaining'])->toBe(1)
        ->and($loadedPreviewColors['total'])->toBe(2);

    Image::create([
        'product_id' => $product->id,
        'image_url' => 'https://example.com/navy.jpg',
        'variation_option_id' => $navyOption->id,
        'order_by' => 0,
    ]);

    $selectedOption = $product->getVariationOptionFromRequest(Request::create('/?colore=navy'));

    expect($selectedOption)->toBeInstanceOf(VariationOption::class)
        ->and($selectedOption->id)->toBe($navyOption->id);
});

it('resolves single and multi-colour variation option swatches', function () {
    $singleColor = VariationOption::factory()->make([
        'name' => 'Verde Bandiera',
        'value' => null,
        'color_hex' => null,
    ]);
    $multiColor = VariationOption::factory()->make([
        'name' => 'Bianco/Navy',
        'value' => null,
        'color_hex' => null,
    ]);
    $customColor = VariationOption::factory()->make([
        'name' => 'Custom',
        'value' => null,
        'color_hex' => '#123456',
    ]);

    expect($singleColor->getHexColors())->toBe(['#009246'])
        ->and($multiColor->getHexColors())->toBe(['#ffffff', '#000080'])
        ->and($customColor->getHexColors())->toBe(['#123456']);
});

it('delegates print sheet and billed area calculations', function () {
    $product = Product::factory()->make([
        'sheet_width' => 200,
        'sheet_height' => 100,
        'min_area' => 0.6,
    ]);

    expect($product->getSheetsNeeded(180, 220))->toBe([
        'sheets' => 3,
        'sheets_x' => 1,
        'sheets_y' => 3,
        'exceeds' => true,
    ])->and($product->calculateItemsPerSheet(80, 40))->toBe(4)
        ->and($product->calculateTotalBilledArea(2, 1000, 500))->toBe(1.2);
});
