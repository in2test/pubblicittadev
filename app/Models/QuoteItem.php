<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
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
