<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PrintPlacementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'sort_order'])]
class PrintPlacement extends Model
{
    /** @use HasFactory<PrintPlacementFactory> */
    use HasFactory;

    public function productVariations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_print_placement')
            ->withPivot('additional_price')
            ->withTimestamps();
    }
}
