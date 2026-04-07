<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_whatsapp',
        'total_items',
        'total_price',
        'status',
        'notes',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }
}
