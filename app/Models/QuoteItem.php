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
 * QuoteItem Model
 *
 * Represents a specific product requested within a quote.
 * It captures the snapshot of the product configuration (color, quantity, price)
 * at the time the quote was requested, ensuring the price remains valid
 * regardless of future product changes.
 *
 * @property int $id
 * @property int $quote_id
 * @property int $product_id
 * @property int $quantity
 * @property numeric $unit_price
 * @property numeric $subtotal
 * @property array<array-key, mixed>|null $customization_json
 * @property string|null $design_file_path
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Product $product
 * @property-read Quote $quote
 *
 * @method static Builder<static>|QuoteItem newModelQuery()
 * @method static Builder<static>|QuoteItem newQuery()
 * @method static Builder<static>|QuoteItem query()
 * @method static Builder<static>|QuoteItem whereCreatedAt($value)
 * @method static Builder<static>|QuoteItem whereCustomizationJson($value)
 * @method static Builder<static>|QuoteItem whereDesignFilePath($value)
 * @method static Builder<static>|QuoteItem whereId($value)
 * @method static Builder<static>|QuoteItem whereProductId($value)
 * @method static Builder<static>|QuoteItem whereQuantity($value)
 * @method static Builder<static>|QuoteItem whereQuoteId($value)
 * @method static Builder<static>|QuoteItem whereSubtotal($value)
 * @method static Builder<static>|QuoteItem whereUnitPrice($value)
 * @method static Builder<static>|QuoteItem whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'quote_id',
    'product_id',
    'quantity',
    'unit_price',
    'subtotal',
    'customization_json',
    'design_file_path',
])]
class QuoteItem extends Model
{
    use HasFactory;

    protected $casts = [
        'customization_json' => 'array',
    ];

    /**
     * Get the quote this item belongs to.
     *
     * @return BelongsTo The relationship with the parent quote.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Get the product associated with this quote item.
     *
     * @return BelongsTo The relationship with the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
