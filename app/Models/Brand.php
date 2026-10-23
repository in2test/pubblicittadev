<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Brand Model
 *
 * Represents a brand associated with products.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @property-read Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @method static \Database\Factories\BrandFactory factory($count = null, $state = [])
 * @method static Builder<static>|Brand newModelQuery()
 * @method static Builder<static>|Brand newQuery()
 * @method static Builder<static>|Brand query()
 * @method static Builder<static>|Brand whereCreatedAt($value)
 * @method static Builder<static>|Brand whereId($value)
 * @method static Builder<static>|Brand whereName($value)
 * @method static Builder<static>|Brand whereSlug($value)
 * @method static Builder<static>|Brand whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[Fillable([
    'name',
    'slug',
])]
#[RouteKey('slug')]
class Brand extends Model implements HasMedia
{
    /**
     * @use HasFactory<BrandFactory>
     */
    use HasFactory, InteractsWithMedia;

    /**
     * Get the products associated with this brand.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Media Library Setup
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logos')
            ->useDisk('public');
    }

    /**
     * Media Library Setup
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->format('png');

        $this->addMediaConversion('medium')
            ->width(600)
            ->height(600)
            ->sharpen(10)
            ->format('png');
    }

    /**
     * Boot the model.
     */
    public static function boot(): void
    {
        parent::boot();

        static::creating(function (Brand $brand) {
            if (empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }
        });
    }
}
