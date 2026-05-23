<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncProductImagesJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Synchronize product images (phase 1: non-color images)')]
#[Signature('sync:product-images')]
class SyncProductImagesCommand extends Command
{
    public function handle(): int
    {
        SyncProductImagesJob::dispatch();
        $this->info('Product images synchronization dispatched.');

        return 0;
    }
}
