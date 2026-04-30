<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Log;
use Exception;

class CacheRemoteImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected Product $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function handle(): void
    {
        try {
            $remoteImages = $this->product->remote_images ?? [];
            $updated = [];
            foreach ($remoteImages as $idx => $img) {
                $id = $img['id'] ?? 'img_'.$idx;
                $thumbUrl = $img['thumb'] ?? '';
                $mediumUrl = $img['medium'] ?? '';
                $thumbPath = 'cache/remote/'.$id.'/thumb.webp';
                $mediumPath = 'cache/remote/'.$id.'/medium.webp';

                // If already cached, keep existing URLs
                $thumbCached = Storage::disk('public')->exists($thumbPath);
                $mediumCached = Storage::disk('public')->exists($mediumPath);

                if ($thumbCached && $mediumCached) {
                    $img['thumb'] = Storage::disk('public')->url($thumbPath);
                    $img['medium'] = Storage::disk('public')->url($mediumPath);
                    $updated[] = $img;
                    continue;
                }

                // Download and cache derivatives if URLs are present
                if ($thumbUrl) {
                    try {
                        $thumbData = Http::get($thumbUrl)->body();
                        $thumbImg = Image::make($thumbData)->resize(150, 150)->encode('webp');
                        Storage::disk('public')->put($thumbPath, (string) $thumbImg);
                        $img['thumb'] = Storage::disk('public')->url($thumbPath);
                    } catch (Exception $e) {
                        Log::warning("Remote image thumb cache failed for {$id}: {$e->getMessage()}");
                    }
                }
                if ($mediumUrl) {
                    try {
                        $mediumData = Http::get($mediumUrl)->body();
                        $mediumImg = Image::make($mediumData)->resize(600, 600)->encode('webp');
                        Storage::disk('public')->put($mediumPath, (string) $mediumImg);
                        $img['medium'] = Storage::disk('public')->url($mediumPath);
                    } catch (Exception $e) {
                        Log::warning("Remote image medium cache failed for {$id}: {$e->getMessage()}");
                    }
                }
                $updated[] = $img;
            }

            // Persist updated remote_images with cached URLs
            if (!empty($updated)) {
                $this->product->remote_images = $updated;
                $this->product->save();
            }
        } catch (Exception $e) {
            Log::error("CacheRemoteImagesJob failed for Product {$this->product->id}: {$e->getMessage()}");
        }
    }
}
