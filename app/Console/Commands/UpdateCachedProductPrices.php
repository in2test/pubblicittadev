<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

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

        $bar = $this->output->createProgressBar(Product::query()->count());
        $bar->start();

        Product::query()->chunkById(500, function (Collection $products) use ($bar): void {
            foreach ($products as $product) {
                $product->updateCachedPrices();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('All product prices have been successfully updated.');
    }
}
