<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConvertProductImages implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private Product $product) {}

    public function handle(): void
    {
        $product = $this->product;

        // If conversion mode is not manual, skip automatic processing.
        $mode = $product->image_conversion_mode ?? 'auto';
        if ($mode !== 'manual') {
            \Log::info("ConvertProductImages skipped for Product {$product->id} mode={$mode} (not manual).");

            return;
        }
        // Do not download external URLs automatically. The API-provided URLs should be used as-is
        $urls = is_array($product->external_image_urls) ? $product->external_image_urls : [];
        if ($urls !== []) {
            Log::info("ConvertProductImages: skipping download for product {$product->id} due to external_image_urls presence (use API URLs).");

            return;
        }
    }
}
