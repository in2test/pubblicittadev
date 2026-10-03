<?php

declare(strict_types=1);

use App\Models\Product;
use App\Services\ProductStartingPriceService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

it('refreshes all products in bounded query batches', function () {
    Product::factory()
        ->count(501)
        ->create(['category_id' => null]);

    $startingPriceService = Mockery::mock(ProductStartingPriceService::class);
    $startingPriceService->shouldReceive('updateCachedPrices')->times(501);
    app()->instance(ProductStartingPriceService::class, $startingPriceService);

    $productQueries = [];
    DB::listen(function (QueryExecuted $query) use (&$productQueries): void {
        $sql = strtolower(ltrim($query->sql));

        if (str_starts_with($sql, 'select') && str_contains($sql, 'products')) {
            $productQueries[] = $sql;
        }
    });

    $this->artisan('app:update-product-prices')
        ->assertExitCode(0);

    expect($productQueries)->toHaveCount(3)
        ->and($productQueries[0])->toContain('count(')
        ->and($productQueries[1])->toContain('limit 500')
        ->and($productQueries[2])->toContain('limit 500');
});
