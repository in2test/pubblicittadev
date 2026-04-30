<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Product;
use App\Jobs\CacheRemoteImagesJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Exception;

class CacheProductImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected Product $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function handle(): void
    {
        // Trigger existing image conversion for all images of the product
        try {
            $mediaItems = $this->product->getMedia('images');
            foreach ($mediaItems as $media) {
                // Access conversions to trigger on-demand generation via existing conversion pipeline
                // These conversions should be defined on the Media model (e.g., thumb and medium)
                if (method_exists($media, 'getUrl')) {
                    try {
                        $media->getUrl('thumb');
                    } catch (Exception $e) {
                        Log::warning("Image cache: failed generating 'thumb' for media {$media->id}: {$e->getMessage()}");
                    }
                    try {
                        $media->getUrl('medium');
                    } catch (Exception $e) {
                        Log::warning("Image cache: failed generating 'medium' for media {$media->id}: {$e->getMessage()}");
                    }
                }
            }

            // If there are remote_images, trigger caching for those as well
            if (!empty($this->product->remote_images ?? [])) {
                CacheRemoteImagesJob::dispatch($this->product);
            }
        } catch (Exception $e) {
            Log::error("Image cache: failed for product {$this->product->id}: {$e->getMessage()}");
        }
    }
}
