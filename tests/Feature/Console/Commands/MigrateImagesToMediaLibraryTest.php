<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

it('eager loads product and category records while migrating images', function () {
    $product = Product::factory()->create();
    $category = Category::factory()->create();
    Image::factory()->for($product)->create(['image_path' => null, 'image_url' => null]);
    Image::factory()->for($category)->create(['image_path' => null, 'image_url' => null]);

    $relationQueries = [];
    DB::listen(function (QueryExecuted $query) use (&$relationQueries): void {
        $sql = strtolower(ltrim($query->sql));
        if (str_starts_with($sql, 'select') && (str_contains($sql, 'products') || str_contains($sql, 'categories'))) {
            $relationQueries[] = $sql;
        }
    });

    $this->artisan('app:migrate-images-to-media-library')
        ->assertExitCode(0);

    expect($relationQueries)->toHaveCount(2)
        ->and($relationQueries[0])->toContain('products')
        ->and($relationQueries[1])->toContain('categories');
});
