<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\OrderItemFactory;
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
 * @property string|null $design_file_path
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property string $work_status
 *
 * @method static \Database\Factories\OrderItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereCustomizationJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereDesignFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereWorkStatus($value)
 *
 * @mixin \Eloquent
 */
/**
 * @use HasFactory<OrderItemFactory>
 */
class OrderItem extends Model
{
    /**
     * @use HasFactory<OrderItemFactory>
     */
    use HasFactory;

    protected $casts = [
        'customization_json' => 'array',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Relationship: OrderItem -> Order.
     * Get the parent order associated with this item.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relationship: OrderItem -> Product.
     * Get the product that this order item represents.
     *
     * @return BelongsTo<Product, $this>
     */
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
