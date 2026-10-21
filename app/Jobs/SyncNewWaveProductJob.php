<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\SyncLevel;
use App\Enums\SyncStatus;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncNewWaveProductJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Product|int $productId,
        public SyncLevel $level = SyncLevel::Full,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ProductAvailabilityService $service): void
    {
        $product = $this->resolveProduct();

        if (! $product instanceof Product) {
            Log::warning("SyncNewWaveProductJob: Product {$this->productKey()} not found, skipping.");

            return;
        }

        $product->update(['sync_status' => SyncStatus::Syncing, 'sync_progress' => 0]);

        try {
            $service->syncByLevel($product, $this->level);

            $product->update([
                'sync_status' => SyncStatus::Synced,
                'sync_progress' => 100,
                'synced_at' => now(),
            ]);
        } catch (Exception $e) {
            $product->update(['sync_status' => SyncStatus::Failed, 'sync_progress' => 0]);
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $product = $this->resolveProduct();

        if ($product instanceof Product) {
            $product->update(['sync_status' => SyncStatus::Failed, 'sync_progress' => 0]);
        }
    }

    /**
     * Resolve the product the job works on.
     *
     * A Product instance is accepted for convenience, but the lookup always goes
     * through the primary key: passing a model to find() would be interpreted as
     * a list of attributes and resolve to the wrong record.
     */
    private function resolveProduct(): ?Product
    {
        return Product::find($this->productKey());
    }

    private function productKey(): int|string
    {
        return $this->productId instanceof Product ? $this->productId->getKey() : $this->productId;
    }
}
