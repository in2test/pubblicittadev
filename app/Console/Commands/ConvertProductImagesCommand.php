<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use App\Jobs\ConvertProductImages;
use Illuminate\Console\Command;

class ConvertProductImagesCommand extends Command
{
    protected $signature = 'products:convert-images {product_id}';

    protected $description = 'Convert images for a given product using external URLs or local files';

    public function handle(): int
    {
        $productId = (int) $this->argument('product_id');
        $product = Product::find($productId);

        if (!$product) {
            $this->error("Product with ID {$productId} not found.");
            return 1;
        }

        ConvertProductImages::dispatch($product);
        $this->info("Dispatched image conversion for Product ID {$product->id}.");
        return 0;
    }
}
