<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CategoryQuantityDiscount Model
 *
 * Defines quantity-based discounts for all products within a specific category.
 * This allows the business to offer lower unit prices as the total order quantity
 * for a category increases.
 */
#[Fillable([
    'category_id',
    'min_quantity',
    'max_quantity',
    'discount_type',
    'discount_value',
    'description',
])]
class CategoryQuantityDiscount extends Model
{
    use HasFactory;

    protected $casts = [
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'discount_value' => 'decimal:4',
    ];

    /**
     * Get the category associated with this discount tier.
     *
     * @return BelongsTo The relationship with the category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
