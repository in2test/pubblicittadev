<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductAvailabilityService;
use Illuminate\Console\Command;

class SyncProductQuantities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-product-quantities {--product= : Optional product SKU to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize product variation quantities and availability from NWG Gateway API';

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
