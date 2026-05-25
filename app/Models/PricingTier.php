<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int|null $product_sku_id
 * @property int|null $print_side_id
 * @property int $min_quantity
 * @property int|null $max_quantity
 * @property string $price_per_unit
 * @property bool $is_custom_price
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read PrintSide|null $printSide
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
 * @method static Builder<static>|PricingTier wherePrintSideId($value)
 *
 * @property Builder<static>|PricingTier whereProductId($value)
 * @property Builder<static>|PricingTier whereProductSkuId($value)
 * @property Builder<static>|PricingTier whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'product_id',
    'product_sku_id',
    'print_side_id',
    'min_quantity',
    'max_quantity',
    'price_per_unit',
    'is_custom_price',
])]
class PricingTier extends Model
{
    use HasFactory;

    protected $casts = [
        'is_custom_price' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function printSide(): BelongsTo
    {
        return $this->belongsTo(PrintSide::class);
    }

    public function productSku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class);
    }
}
