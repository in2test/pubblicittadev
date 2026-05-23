<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductAvailabilityService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Synchronize product variation quantities and availability from NWG Gateway API')]
#[Signature('app:sync-product-quantities {--product= : Optional product SKU to sync}')]
class SyncProductQuantities extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ProductAvailabilityService $availabilityService): int
    {
        $productSku = $this->option('product');

        $products = Product::query()
            ->when($productSku, fn ($query) => $query->where('sku', $productSku))
            ->get();

        if ($products->isEmpty()) {
            $this->error('No products found to sync.');

            return 1;
        }

        $this->info("Starting sync for {$products->count()} products...");

        foreach ($products as $product) {
            $this->info("Syncing product: {$product->sku} ({$product->name})");

            $availabilityService->syncProduct($product);
        }

        $this->info('Synchronization completed.');

        return 0;
    }
}
