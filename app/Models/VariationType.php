<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'presentation_type',
])]
class VariationType extends Model
{
    use HasFactory;

    public function options(): HasMany
    {
        return $this->hasMany(VariationOption::class)->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_variation_types')
            ->using(ProductVariationType::class)
            ->withPivot('id', 'has_images', 'affects_price', 'sort_order')
            ->orderByPivot('sort_order');
    }

    public function productVariationTypes(): HasMany
    {
        return $this->hasMany(ProductVariationType::class);
    }
}
