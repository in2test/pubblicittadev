<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PrintSideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'sort_order'])]
class PrintSide extends Model
{
    /** @use HasFactory<PrintSideFactory> */
    use HasFactory;

    public function productVariations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }
}
