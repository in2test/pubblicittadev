<?php

use App\Models\Image;
use App\Models\Product;
use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->product = Product::factory()->create(['type' => Product::TYPE_NEWWAVE]);

    $this->colorType = VariationType::factory()->create(['name' => 'Colore']);

    $this->optionRed = VariationOption::factory()->create([
        'variation_type_id' => $this->colorType->id,
        'name' => 'Rosso',
        'value' => 'red',
    ]);

    $this->optionBlue = VariationOption::factory()->create([
        'variation_type_id' => $this->colorType->id,
        'name' => 'Blu',
        'value' => 'blue',
    ]);

    // 5 images for red, 5 for blue, 2 generic
    foreach (range(1, 5) as $i) {
        Image::create([
            'product_id' => $this->product->id,
            'image_url' => "https://example.com/red-{$i}.jpg",
            'variation_option_id' => $this->optionRed->id,
            'order_by' => $i,
        ]);
    }

    foreach (range(1, 5) as $i) {
        Image::create([
            'product_id' => $this->product->id,
            'image_url' => "https://example.com/blue-{$i}.jpg",
            'variation_option_id' => $this->optionBlue->id,
            'order_by' => $i,
        ]);
    }

    Image::create([
        'product_id' => $this->product->id,
        'image_url' => 'https://example.com/generic-1.jpg',
        'variation_option_id' => null,
        'order_by' => 1,
    ]);
    Image::create([
        'product_id' => $this->product->id,
        'image_url' => 'https://example.com/generic-2.jpg',
        'variation_option_id' => null,
        'order_by' => 2,
    ]);
});

it('returns only images for the requested variation option', function () {
    $images = $this->product->getImagesForOption($this->optionRed->id);

    expect($images)->toHaveCount(5);

    foreach ($images as $image) {
        expect($image->variation_option_id)->toBe($this->optionRed->id);
        expect($image->url)->toContain('red-');
    }
});

it('returns only generic images when null is passed', function () {
    $images = $this->product->getImagesForOption(null);

    expect($images)->toHaveCount(2);

    foreach ($images as $image) {
        expect($image->url)->toContain('generic-');
    }
});

it('does not return all images for an option request (avoids the 167-images problem)', function () {
    $images = $this->product->getImagesForOption($this->optionBlue->id);

    // Should return only 5 blue images, NOT all 12
    expect($images)->toHaveCount(5);
    expect($images->pluck('url')->every(fn ($url) => str_contains($url, 'blue-')))->toBeTrue();
});

it('returns an empty collection for an option with no images', function () {
    $optionGreen = VariationOption::factory()->create([
        'variation_type_id' => $this->colorType->id,
        'name' => 'Verde',
        'value' => 'green',
    ]);

    $images = $this->product->getImagesForOption($optionGreen->id);

    expect($images)->toHaveCount(0);
});
