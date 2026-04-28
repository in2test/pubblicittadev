<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'color_name',
    'color_hex',
    'color_code',
    'sort_order',
])]
class Color extends Model
{
    use HasFactory;

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }
}
