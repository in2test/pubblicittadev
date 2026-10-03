<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductSku;
use App\Models\VariationOption;
use App\Models\VariationType;
use App\Services\ProductOutletService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('updates only selected variant SKUs in one write', function () {
    $product = Product::factory()->create();
    $variationType = VariationType::factory()->create();
    $red = VariationOption::factory()->for($variationType, 'type')->create(['value' => 'red']);
    $blue = VariationOption::factory()->for($variationType, 'type')->create(['value' => 'blue']);
    $green = VariationOption::factory()->for($variationType, 'type')->create(['value' => 'green']);

    $redSku = ProductSku::factory()->for($product)->create(['is_outlet' => false, 'override_price' => 4]);
    $blueSku = ProductSku::factory()->for($product)->create(['is_outlet' => true, 'override_price' => 5]);
    $greenSku = ProductSku::factory()->for($product)->create(['is_outlet' => true, 'override_price' => 6]);
    $redSku->options()->attach($red);
    $blueSku->options()->attach($blue);
    $greenSku->options()->attach($green);

    $writes = [];
    DB::listen(function (QueryExecuted $query) use (&$writes): void {
        $sql = strtolower(ltrim($query->sql));
        if (str_contains($sql, 'product_skus') && ! str_starts_with($sql, 'select')) {
            $writes[] = $sql;
        }
    });

    app(ProductOutletService::class)->updateSkuOutletSettings($product, false, null, [
        ['option_id' => $red->id, 'is_outlet' => true, 'outlet_price' => 12.5],
        ['option_id' => $blue->id, 'is_outlet' => false, 'outlet_price' => null],
    ]);

    expect($redSku->fresh()->is_outlet)->toBeTrue()
        ->and((float) $redSku->fresh()->override_price)->toBe(12.5);
    expect($blueSku->fresh()->is_outlet)->toBeFalse()
        ->and($blueSku->fresh()->override_price)->toBeNull();
    expect($greenSku->fresh()->is_outlet)->toBeTrue()
        ->and((float) $greenSku->fresh()->override_price)->toBe(6.0)
        ->and($writes)->toHaveCount(1);
});

it('applies a product outlet price to every SKU when no variant prices are provided', function () {
    $product = Product::factory()->create();
    $firstSku = ProductSku::factory()->for($product)->create(['is_outlet' => false, 'override_price' => null]);
    $secondSku = ProductSku::factory()->for($product)->create(['is_outlet' => false, 'override_price' => 3]);

    app(ProductOutletService::class)->updateSkuOutletSettings($product, true, 7.5, []);

    expect($firstSku->fresh()->is_outlet)->toBeTrue()
        ->and((float) $firstSku->fresh()->override_price)->toBe(7.5);
    expect($secondSku->fresh()->is_outlet)->toBeTrue()
        ->and((float) $secondSku->fresh()->override_price)->toBe(7.5);
});
