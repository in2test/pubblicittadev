<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Color Model
 *
 * Represents a specific color available for products.
 * This model is used to define visual variations and their associated
 * hex codes and display names.
 */
#[Fillable([
    'color_name',
    'color_hex',
    'color_code',
    'sort_order',
])]
class Color extends Model
{
    use HasFactory;

    /**
     * Get the variations associated with this color.
     *
     * @return HasMany The relationship with product variations.
     */
    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }
}
