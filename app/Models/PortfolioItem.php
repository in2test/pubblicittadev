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

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string|null $link
 * @property int $is_featured
 * @property int $sort_order
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @method static \Database\Factories\PortfolioItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PortfolioItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PortfolioItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PortfolioItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PortfolioItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PortfolioItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PortfolioItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PortfolioItem whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PortfolioItem whereLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PortfolioItem whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PortfolioItem whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PortfolioItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
