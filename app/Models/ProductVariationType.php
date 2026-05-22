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

/**
 * @property int $id
 * @property int $product_id
 * @property int $variation_type_id
 * @property bool $has_images
 * @property bool $affects_price
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
 * @method static Builder<static>|ProductVariationType whereAffectsPrice($value)
 * @method static Builder<static>|ProductVariationType whereCreatedAt($value)
 * @method static Builder<static>|ProductVariationType whereHasImages($value)
 * @method static Builder<static>|ProductVariationType whereId($value)
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
    'affects_price',
    'sort_order',
])]
#[Table(name: 'product_variation_types', key: 'id')]
class ProductVariationType extends Pivot
{
    public $incrementing = true;

    protected $casts = [
        'has_images' => 'boolean',
        'affects_price' => 'boolean',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(VariationType::class, 'variation_type_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductVariationOption::class, 'product_variation_type_id');
    }
}
