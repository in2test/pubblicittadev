<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\SyncStatus;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncNewWaveProductJob implements ShouldQueue
{
    use Queueable;

    public $tries = 1;

    public $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Product $product,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ProductAvailabilityService $service): void
    {
        $this->product->update(['sync_status' => SyncStatus::Syncing, 'sync_progress' => 0]);

        try {
            $service->syncProduct($this->product);

            $this->product->update([
                'sync_status' => SyncStatus::Synced,
                'sync_progress' => 100,
                'synced_at' => now(),
            ]);
        } catch (Exception $e) {
            $this->product->update(['sync_status' => SyncStatus::Failed, 'sync_progress' => 0]);
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->product->update(['sync_status' => SyncStatus::Failed, 'sync_progress' => 0]);
    }
}
