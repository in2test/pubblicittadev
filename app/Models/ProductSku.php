<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ProductSkuFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $product_id
 * @property string|null $sku
 * @property int $quantity
 * @property bool $is_available
 * @property numeric|null $override_price
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, VariationOption> $options
 * @property-read int|null $options_count
 * @property-read Collection<int, PricingTier> $pricingTiers
 * @property-read int|null $pricing_tiers_count
 *
 * @method static ProductSkuFactory factory($count = null, $state = [])
 * @method static Builder<static>|ProductSku newModelQuery()
 * @method static Builder<static>|ProductSku newQuery()
 * @method static Builder<static>|ProductSku query()
 * @method static Builder<static>|ProductSku whereCreatedAt($value)
 * @method static Builder<static>|ProductSku whereId($value)
 * @method static Builder<static>|ProductSku whereIsAvailable($value)
 * @method static Builder<static>|ProductSku whereOverridePrice($value)
 * @method static Builder<static>|ProductSku whereProductId($value)
 * @method static Builder<static>|ProductSku whereQuantity($value)
 * @method static Builder<static>|ProductSku whereSku($value)
 * @method static Builder<static>|ProductSku whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'product_id',
    'sku',
    'quantity',
    'is_available',
    'override_price',
])]
class ProductSku extends Model
{
    /**
     * @use HasFactory<ProductSkuFactory>
     */
    use HasFactory;

    protected $casts = [
        'is_available' => 'boolean',
    ];

    /**
     * @return BelongsToMany<VariationOption, $this>
     */
    public function options(): BelongsToMany
    {
        return $this->belongsToMany(VariationOption::class, 'product_sku_options', 'product_sku_id', 'variation_option_id');
    }

    /**
     * @return HasMany<PricingTier, $this>
     */
    public function pricingTiers(): HasMany
    {
        return $this->hasMany(PricingTier::class);
    }

    public function setQuantityAttribute(mixed $value): void
    {
        $this->attributes['quantity'] = $value === null ? -1 : (int) $value;
    }
}
