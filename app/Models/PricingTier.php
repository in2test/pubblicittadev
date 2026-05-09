<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int $min_quantity
 * @property int|null $max_quantity
 * @property string $price_per_unit
 */
#[Fillable([
    'product_id',
    'min_quantity',
    'max_quantity',
    'price_per_unit',
])]
class PricingTier extends Model
{
    use HasFactory;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
