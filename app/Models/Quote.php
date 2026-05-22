<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Quote Model
 *
 * Represents a customer's request for a price quote.
 * It stores customer contact information, total items, and the overall price,
 * acting as the header for a set of specific QuoteItems.
 *
 * @property int $id
 * @property string $quote_number
 * @property string $customer_name
 * @property string $customer_email
 * @property string|null $customer_phone
 * @property string|null $customer_whatsapp
 * @property int $total_items
 * @property numeric $total_price
 * @property string $status
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property int|null $user_id
 * @property-read Collection<int, QuoteItem> $items
 * @property-read int|null $items_count
 * @property-read User|null $user
 *
 * @method static Builder<static>|Quote newModelQuery()
 * @method static Builder<static>|Quote newQuery()
 * @method static Builder<static>|Quote query()
 * @method static Builder<static>|Quote whereCreatedAt($value)
 * @method static Builder<static>|Quote whereCustomerEmail($value)
 * @method static Builder<static>|Quote whereCustomerName($value)
 * @method static Builder<static>|Quote whereCustomerPhone($value)
 * @method static Builder<static>|Quote whereCustomerWhatsapp($value)
 * @method static Builder<static>|Quote whereId($value)
 * @method static Builder<static>|Quote whereNotes($value)
 * @method static Builder<static>|Quote whereQuoteNumber($value)
 * @method static Builder<static>|Quote whereStatus($value)
 * @method static Builder<static>|Quote whereTotalItems($value)
 * @method static Builder<static>|Quote whereTotalPrice($value)
 * @method static Builder<static>|Quote whereUpdatedAt($value)
 * @method static Builder<static>|Quote whereUserId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'user_id',
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
     * Get the user who requested this quote.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

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
