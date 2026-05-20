<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable([
    'order_id',
    'product_id',
    'quantity',
    'unit_price',
    'subtotal',
    'customization_json',
    'design_file_path',
    'work_status',
])]
/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int $quantity
 * @property float $unit_price
 * @property float $subtotal
 * @property array $customization_json
 * @property-read Product $product
 * @property-read Order $order
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

    public function getWorkStatusLabel(): string
    {
        return match ($this->work_status) {
            'pending' => 'In Attesa',
            'awaiting_file' => 'Attendiamo File',
            'processing' => 'In Lavorazione',
            'ready' => 'Pronto per Spedizione',
            'shipped' => 'Spedito',
            'completed' => 'Completato',
            default => $this->work_status,
        };
    }

    #[Override]
    protected static function booted(): void
    {
        static::saved(function (OrderItem $item) {
            if ($item->wasChanged('work_status')) {
                $item->loadMissing('order');
                /** @var Order $order */
                $order = $item->order;
                $order->updateWorkStatusFromItems();
            }
        });

        static::deleted(function (OrderItem $item) {
            $item->loadMissing('order');
            /** @var Order $order */
            $order = $item->order;
            $order->updateWorkStatusFromItems();
        });
    }
}
