<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PrintPlacementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PrintPlacement Model
 *
 * Defines possible print locations on a garment (e.g., Chest, Back, Sleeve).
 * Placements are linked to products with an associated additional price.
 */
#[Fillable(['name', 'description', 'sort_order'])]
class PrintPlacement extends Model
{
    /** @use HasFactory<PrintPlacementFactory> */
    use HasFactory;

    /**
     * Get the product variations that use this print placement.
     *
     * @return HasMany The relationship with product variations.
     */
    public function productVariations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    /**
     * Get the products that support this print placement.
     *
     * @return BelongsToMany The relationship with products, including
     *                       the pivot table data for additional pricing.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_print_placement')
            ->withPivot('additional_price')
            ->withTimestamps();
    }
}
