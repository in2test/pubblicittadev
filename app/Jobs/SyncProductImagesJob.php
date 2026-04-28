<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Bus\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncProductImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        // no-op
    }

    public function handle(): void
    {
        Log::info('SyncProductImagesJob started');

        $start = microtime(true);

        // Iterate all products and sync non-color images
        Product::with('category')->chunk(50, function ($products) {
            foreach ($products as $product) {
                foreach ($product->getMedia('images') as $media) {
                    // Trigger conversions by accessing the URLs (will create conversions lazily)
                    try {
                        $media->getUrl('thumbnail');
                        $media->getUrl('medium');
                        $media->getUrl('large');
                    } catch (Throwable $e) {
                        Log::warning('Media conversion failed for media id '.$media->id.': '.$e->getMessage());
                    }
                }
            }
        });

        $elapsed = microtime(true) - $start;
        Log::info('SyncProductImagesJob finished in '.round($elapsed, 2).'s');
    }
}
