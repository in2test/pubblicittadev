<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PortfolioItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable([
    'title',
    'description',
    'link',
    'is_featured',
    'sort_order',
])]
class PortfolioItem extends Model implements HasMedia
{
    /** @use HasFactory<PortfolioItemFactory> */
    use HasFactory;

    use InteractsWithMedia;

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('large')
            ->nonQueued()
            ->width(1200)
            ->height(900)
            ->format('webp')
            ->quality(80);

        $this->addMediaConversion('medium')
            ->nonQueued()
            ->width(600)
            ->height(450)
            ->format('webp')
            ->quality(80);
    }
}
