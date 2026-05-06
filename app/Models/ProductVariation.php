<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductVariationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductVariation Model
 *
 * This model serves as a pivot that defines a unique combination of product,
 * color, size, and print placement. It tracks inventory (quantity) and
 * availability for specific product configurations.
 */
#[Fillable(['product_id', 'color_id', 'size_id', 'print_placement_id', 'print_side_id', 'sku', 'quantity', 'is_available'])]
class ProductVariation extends Model
{
    /** @use HasFactory<ProductVariationFactory> */
    use HasFactory;

    /**
     * Get the product associated with this variation.
     *
     * @return BelongsTo The relationship with the parent product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the color associated with this variation.
     *
     * @return BelongsTo The relationship with the color model.
     */
    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    /**
     * Get the size associated with this variation.
     *
     * @return BelongsTo The relationship with the size model.
     */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    /**
     * Get the print placement associated with this variation.
     *
     * @return BelongsTo The relationship with the print placement.
     */
    public function printPlacement(): BelongsTo
    {
        return $this->belongsTo(PrintPlacement::class);
    }

    /**
     * Get the print side associated with this variation.
     *
     * @return BelongsTo The relationship with the print side.
     */
    public function printSide(): BelongsTo
    {
        return $this->belongsTo(PrintSide::class);
    }
}
