<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }
}
