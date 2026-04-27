<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncProductImagesJob;
use Illuminate\Console\Command;

class SyncProductImagesCommand extends Command
{
    protected $signature = 'sync:product-images';

    protected $description = 'Synchronize product images (phase 1: non-color images)';

    public function handle(): int
    {
        SyncProductImagesJob::dispatch();
        $this->info('Product images synchronization dispatched.');

        return 0;
    }
}
