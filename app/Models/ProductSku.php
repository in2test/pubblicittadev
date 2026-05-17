<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'product_id',
    'sku',
    'quantity',
    'is_available',
    'override_price',
])]
class ProductSku extends Model
{
    use HasFactory;

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(VariationOption::class, 'product_sku_options', 'product_sku_id', 'variation_option_id');
    }
}
