<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PrintSideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PrintSide Model
 *
 * Defines the side of the garment where a print is placed (e.g., Front, Back, Left, Right).
 * Used to differentiate between placements on the same area of the garment.
 */
#[Fillable(['name', 'description', 'sort_order'])]
class PrintSide extends Model
{
    /** @use HasFactory<PrintSideFactory> */
    use HasFactory;

    /**
     * Get the product variations that use this print side.
     *
     * @return HasMany The relationship with product variations.
     */
    public function productVariations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    /**
     * Get the products that support this print side.
     *
     * @return BelongsToMany The relationship with products.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_print_side')
            ->withTimestamps();
    }
}
