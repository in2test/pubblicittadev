<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Image;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaObserver
{
    /**
     * Handle the Media "deleted" event.
     */
    public function deleted(Media $media): void
    {
        $remoteUrl = data_get($media->getCustomProperty('remote_resource_url'), 'standard');

        if ($remoteUrl) {
            Image::where('image_url', $remoteUrl)->delete();
        }
    }

    /**
     * Handle the Media "force deleted" event.
     */
    public function forceDeleted(Media $media): void
    {
        $remoteUrl = data_get($media->getCustomProperty('remote_resource_url'), 'standard');

        if ($remoteUrl) {
            Image::where('image_url', $remoteUrl)->delete();
        }
    }
}
