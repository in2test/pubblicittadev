<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaTypes\Media;
use Spatie\MediaLibrary\Models\Media as MediaModel;
use Exception;

class CacheMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected MediaModel $media;

    public function __construct(MediaModel $media)
    {
        $this->media = $media;
    }

    public function handle(): void
    {
        try {
            if (method_exists($this->media, 'getUrl')) {
                // Trigger conversions for thumb and medium
                $this->media->getUrl('thumb');
                $this->media->getUrl('medium');
            }
        } catch (Exception $e) {
            Log::error("Image cache (media {$this->media->id}): {$e->getMessage()}");
        }
    }
}
