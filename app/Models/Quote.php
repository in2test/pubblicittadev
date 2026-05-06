<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Quote Model
 *
 * Represents a customer's request for a price quote.
 * It stores customer contact information, total items, and the overall price,
 * acting as the header for a set of specific QuoteItems.
 */
#[Fillable([
    'quote_number',
    'customer_name',
    'customer_email',
    'customer_phone',
    'customer_whatsapp',
    'total_items',
    'total_price',
    'status',
    'notes',
])]
class Quote extends Model
{
    use HasFactory;

    /**
     * Get the items associated with this quote.
     *
     * @return HasMany The relationship with the individual quote items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }
}
