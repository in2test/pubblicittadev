<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable([
    'product_id',
    'variation_type_id',
    'has_images',
    'affects_price',
    'sort_order',
])]
#[Table(name: 'product_variation_types', key: 'id')]
class ProductVariationType extends Pivot
{
    public $incrementing = true;

    protected $casts = [
        'has_images' => 'boolean',
        'affects_price' => 'boolean',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(VariationType::class, 'variation_type_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductVariationOption::class, 'product_variation_type_id');
    }
}
