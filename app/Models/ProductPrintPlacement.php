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
 * ProductPrintPlacement Model
 *
 * This model represents the pivot relationship between a Product and its
 * available PrintPlacements. It specifically stores the additional price
 * associated with a specific placement for a specific product.
 *
 * @property int $id
 * @property int $product_id
 * @property int $print_placement_id
 * @property numeric $additional_price
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read PrintPlacement $printPlacement
 * @property-read Product $product
 *
 * @method static Builder<static>|ProductPrintPlacement newModelQuery()
 * @method static Builder<static>|ProductPrintPlacement newQuery()
 * @method static Builder<static>|ProductPrintPlacement query()
 * @method static Builder<static>|ProductPrintPlacement whereAdditionalPrice($value)
 * @method static Builder<static>|ProductPrintPlacement whereCreatedAt($value)
 * @method static Builder<static>|ProductPrintPlacement whereId($value)
 * @method static Builder<static>|ProductPrintPlacement wherePrintPlacementId($value)
 * @method static Builder<static>|ProductPrintPlacement whereProductId($value)
 * @method static Builder<static>|ProductPrintPlacement whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['product_id', 'print_placement_id', 'additional_price'])]
#[Table(name: 'product_print_placement')]
class ProductPrintPlacement extends Model
{
    /**
     * Get the product associated with this placement.
     *
     * @return BelongsTo The relationship with the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the specific print placement associated with this record.
     *
     * @return BelongsTo The relationship with the print placement.
     */
    public function printPlacement(): BelongsTo
    {
        return $this->belongsTo(PrintPlacement::class);
    }
}
