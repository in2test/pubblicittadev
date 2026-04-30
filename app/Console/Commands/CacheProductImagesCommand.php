<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Jobs\CacheProductImagesJob;

class CacheProductImagesCommand extends Command
{
    protected $signature = 'images:cache {product_id}';
    protected $description = 'Queue on-demand caching of product images using existing conversion path';

    public function handle(): int
    {
        $productId = (int) $this->argument('product_id');
        $product = Product::find($productId);

        if (! $product) {
            $this->error("Product with id {$productId} not found");
            return 1;
        }

        CacheProductImagesJob::dispatch($product);
        $this->info("CacheImages job dispatched for Product ID {$productId}");
        return 0;
    }
}
