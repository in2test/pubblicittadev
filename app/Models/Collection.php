<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CollectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Collection Model
 *
 * Represents a curated group of products (bundles/kits) such as
 * "Apertura attività" or "Golfing Kit" that can be promoted together
 * and featured on the homepage hero or banner sections.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_featured
 * @property bool $is_active
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read EloquentCollection<int, Product> $products
 * @property-read int|null $products_count
 *
 * @method static CollectionFactory factory($count = null, $state = [])
 * @method static Builder<static>|Collection active()
 * @method static Builder<static>|Collection featured()
 * @method static Builder<static>|Collection newModelQuery()
 * @method static Builder<static>|Collection newQuery()
 * @method static Builder<static>|Collection query()
 *
 * @mixin Model
 */
#[Fillable(['name', 'slug', 'description', 'is_featured', 'is_active', 'sort_order'])]
#[RouteKey('slug')]
class Collection extends Model implements HasMedia
{
    /** @use HasFactory<CollectionFactory> */
    use HasFactory;

    use InteractsWithMedia;

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'collection_product')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order')
            ->withTimestamps();
    }

    /**
     * @param  Builder<Collection>  $query
     * @return Builder<Collection>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Collection>  $query
     * @return Builder<Collection>
     */
    #[Scope]
    protected function featured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banner')->singleFile();
        $this->addMediaCollection('images');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->nonQueued()
            ->width(300)
            ->height(300)
            ->format('webp')
            ->quality(85);

        $this->addMediaConversion('medium')
            ->nonQueued()
            ->width(800)
            ->height(600)
            ->format('webp')
            ->quality(85);

        $this->addMediaConversion('large')
            ->nonQueued()
            ->width(1920)
            ->height(1080)
            ->format('webp')
            ->quality(85);
    }

    /**
     * Get banner or primary collection image URL.
     */
    public function getBannerUrl(string $conversion = 'large'): ?string
    {
        $banner = $this->getFirstMediaUrl('banner', $conversion) ?: $this->getFirstMediaUrl('banner');
        if ($banner !== '') {
            return $banner;
        }

        $image = $this->getFirstMediaUrl('images', $conversion) ?: $this->getFirstMediaUrl('images');

        return $image !== '' ? $image : null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
