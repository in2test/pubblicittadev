<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'product_id',
    'color_id',
    'quantity',
    'unit_price',
    'subtotal',
    'customization_json',
    'design_file_path',
])]
/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int|null $color_id
 * @property int $quantity
 * @property float $unit_price
 * @property float $subtotal
 * @property array $customization_json
 * @property-read Product $product
 * @property-read Color|null $color
 */
class OrderItem extends Model
{
    use HasFactory;

    protected $casts = [
        'customization_json' => 'array',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }
}
