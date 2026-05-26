<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Pivot between Product and VariationType.
 *
 * Implements HasMedia so each pivot row can carry its own gallery images
 * (e.g. Forex × Thickness-5mm images vs Forex × Thickness-10mm images).
 * The collection "option_images" is keyed; use custom_properties.variation_option_id
 * on each Media record to associate it with a specific option.
 *
 * is_modifier = false → Base variation (determines price tier / SKU)
 * is_modifier = true  → Price modifier (applies a percentage or flat surcharge)
 *
 * @property int $id
 * @property int $product_id
 * @property int $variation_type_id
 * @property bool $has_images
 * @property bool $is_modifier
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, ProductVariationOption> $options
 * @property-read int|null $options_count
 * @property-read VariationType $type
 *
 * @method static Builder<static>|ProductVariationType newModelQuery()
 * @method static Builder<static>|ProductVariationType newQuery()
 * @method static Builder<static>|ProductVariationType query()
 * @method static Builder<static>|ProductVariationType whereCreatedAt($value)
 * @method static Builder<static>|ProductVariationType whereHasImages($value)
 * @method static Builder<static>|ProductVariationType whereId($value)
 * @method static Builder<static>|ProductVariationType whereIsModifier($value)
 * @method static Builder<static>|ProductVariationType whereProductId($value)
 * @method static Builder<static>|ProductVariationType whereSortOrder($value)
 * @method static Builder<static>|ProductVariationType whereUpdatedAt($value)
 * @method static Builder<static>|ProductVariationType whereVariationTypeId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'product_id',
    'variation_type_id',
    'has_images',
    'is_modifier',
    'sort_order',
])]
#[Table(name: 'product_variation_types', key: 'id')]
class ProductVariationType extends Pivot implements HasMedia
{
    use InteractsWithMedia;

    public $incrementing = true;

    protected $casts = [
        'has_images' => 'boolean',
        'is_modifier' => 'boolean',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(VariationType::class, 'variation_type_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductVariationOption::class, 'product_variation_type_id')
            ->orderBy('sort_order');
    }

    /**
     * Register the media collection used for per-option images.
     * Each Media item should have custom_properties.variation_option_id set
     * to link it to a specific option (e.g. 5mm, 10mm).
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('option_images')
            ->useDisk('public');
    }

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
}
