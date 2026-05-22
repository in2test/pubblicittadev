<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_variation_type_id
 * @property int $variation_option_id
 * @property numeric $price_modifier
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read VariationOption $option
 * @property-read ProductVariationType $productVariationType
 *
 * @method static Builder<static>|ProductVariationOption newModelQuery()
 * @method static Builder<static>|ProductVariationOption newQuery()
 * @method static Builder<static>|ProductVariationOption query()
 * @method static Builder<static>|ProductVariationOption whereCreatedAt($value)
 * @method static Builder<static>|ProductVariationOption whereId($value)
 * @method static Builder<static>|ProductVariationOption wherePriceModifier($value)
 * @method static Builder<static>|ProductVariationOption whereProductVariationTypeId($value)
 * @method static Builder<static>|ProductVariationOption whereUpdatedAt($value)
 * @method static Builder<static>|ProductVariationOption whereVariationOptionId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'product_variation_type_id',
    'variation_option_id',
    'price_modifier',
])]
#[Table(name: 'product_variation_options')]
class ProductVariationOption extends Model
{
    public $incrementing = true;

    public function option(): BelongsTo
    {
        return $this->belongsTo(VariationOption::class, 'variation_option_id');
    }

    public function productVariationType(): BelongsTo
    {
        return $this->belongsTo(ProductVariationType::class, 'product_variation_type_id');
    }
}
