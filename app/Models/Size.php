<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'size_name',
    'size',
    'size_code',
    'sort_order',
])]
class Size extends Model
{
    use HasFactory;

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }
}
