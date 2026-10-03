<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductVariationOption;
use App\Models\ProductVariationType;
use App\Models\VariationOption;
use App\Models\VariationType;
use App\Services\ProductVariationTypeSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('attaches unique variation types and options without duplicate rows', function () {
    $product = Product::factory()->create();
    $colorType = VariationType::factory()->create();
    $sizeType = VariationType::factory()->create();
    $red = VariationOption::factory()->for($colorType, 'type')->create();
    $blue = VariationOption::factory()->for($colorType, 'type')->create();
    $medium = VariationOption::factory()->for($sizeType, 'type')->create();
    $variationTypes = [
        ['variation_type_id' => $colorType->id, 'variation_option_ids' => [$red->id, $blue->id]],
        ['variation_type_id' => $sizeType->id, 'variation_option_ids' => [$medium->id]],
    ];
    $synchronizer = app(ProductVariationTypeSynchronizer::class);

    $synchronizer->sync($product, $variationTypes);
    $synchronizer->sync($product, $variationTypes);

    $productVariationTypes = ProductVariationType::query()
        ->where('product_id', $product->id)
        ->get();

    expect($productVariationTypes)->toHaveCount(2)
        ->and(ProductVariationOption::query()
            ->whereIn('product_variation_type_id', $productVariationTypes->modelKeys())
            ->count())->toBe(3)
        ->and($productVariationTypes->every(fn (ProductVariationType $type): bool => $type->is_modifier))->toBeTrue();
});
