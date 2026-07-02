<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property int $id
 * @property int $product_id
 * @property int|null $product_sku_id
 * @property int $min_quantity
 * @property int|null $max_quantity
 * @property string $price_per_unit
 * @property bool $is_custom_price
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ProductSku|null $productSku
 * @property-read Product $product
 *
 * @method static Builder<static>|PricingTier newModelQuery()
 * @method static Builder<static>|PricingTier newQuery()
 * @method static Builder<static>|PricingTier query()
 * @method static Builder<static>|PricingTier whereCreatedAt($value)
 * @method static Builder<static>|PricingTier whereId($value)
 * @method static Builder<static>|PricingTier whereIsCustomPrice($value)
 * @method static Builder<static>|PricingTier whereMaxQuantity($value)
 * @method static Builder<static>|PricingTier whereMinQuantity($value)
 * @method static Builder<static>|PricingTier wherePricePerUnit($value)
 * @method static Builder<static>|PricingTier whereProductId($value)
 * @method static Builder<static>|PricingTier whereProductSkuId($value)
 * @method static Builder<static>|PricingTier whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'product_id',
    'product_sku_id',
    'min_quantity',
    'max_quantity',
    'price_per_unit',
    'is_custom_price',
])]
/**
 * @use HasFactory<PricingTierFactory>
 */
class PricingTier extends Model
{
    protected $casts = [
        'is_custom_price' => 'boolean',
    ];

    #[Override]
    protected static function booted(): void
    {
        static::creating(function (PricingTier $pricingTier) {
            if (! $pricingTier->product_id && $pricingTier->product_sku_id) {
                $sku = ProductSku::find($pricingTier->product_sku_id);
                if ($sku) {
                    $pricingTier->product_id = $sku->product_id;
                }
            }
        });
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductSku, $this>
     */
    public function productSku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class);
    }
}
