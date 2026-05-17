<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_variation_type_id',
    'variation_option_id',
    'price_modifier',
])]
#[Table(name: 'product_variation_options')]
class ProductVariationOption extends Model
{
    public $incrementing = true;

    public function option(): BelongsTo
    {
        return $this->belongsTo(VariationOption::class, 'variation_option_id');
    }

    public function productVariationType(): BelongsTo
    {
        return $this->belongsTo(ProductVariationType::class, 'product_variation_type_id');
    }
}
