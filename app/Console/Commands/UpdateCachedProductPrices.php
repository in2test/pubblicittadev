<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Update the cached starting prices for all products')]
#[Signature('app:update-product-prices')]
class UpdateCachedProductPrices extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting update of cached product prices...');

        $products = Product::all();
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            $product->updateCachedPrices();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('All product prices have been successfully updated.');
    }
}
