<?php

declare(strict_types=1);

namespace App\Listeners;

use Spatie\MediaLibrary\Events\MediaUpdated;
use App\Jobs\CacheMediaJob;

class CacheMediaOnUpdate
{
    public function __invoke(MediaUpdated $event): void
    {
        // Only cache when the media is part of the main images collection
        $media = $event->media;
        if ($media->collection_name === 'images') {
            CacheMediaJob::dispatch($media);
        }
    }
}
