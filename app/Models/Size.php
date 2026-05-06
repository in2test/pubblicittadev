<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Size Model
 *
 * Represents a product size (e.g., S, M, L, XL).
 * Used to define valid size options for product variations.
 */
#[Fillable([
    'size_name',
    'size',
    'size_code',
    'sort_order',
])]
class Size extends Model
{
    use HasFactory;

    /**
     * Get the variations associated with this size.
     *
     * @return HasMany The relationship with product variations.
     */
    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }
}
