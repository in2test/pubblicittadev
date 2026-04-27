<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryQuantityDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'min_quantity',
        'max_quantity',
        'discount_type',
        'discount_value',
        'description',
    ];

    protected $casts = [
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'discount_value' => 'decimal:4',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
