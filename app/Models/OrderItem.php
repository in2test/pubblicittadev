<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WorkStatus;
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
    WorkStatus::class,
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
 * @property WorkStatus $work_status
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

    public function workStatusLabel(): string
    {
        $status = $this->work_status;

        // Handle string values for backward compatibility with legacy data
        if (is_string($status)) {
            return match ($status) {
                'pending' => 'In Attesa',
                'awaiting_file' => 'Attendiamo File',
                'processing' => 'In Lavorazione',
                'ready' => 'Pronto per Spedizione',
                'shipped' => 'Spedito',
                'completed' => 'Completato',
                default => $status,
            };
        }

        return match ($status) {
            WorkStatus::Pending => 'In Attesa',
            WorkStatus::AwaitingFile => 'Attendiamo File',
            WorkStatus::Processing => 'In Lavorazione',
            WorkStatus::Ready => 'Pronto per Spedizione',
            WorkStatus::Shipped => 'Spedito',
            WorkStatus::Completed => 'Completato',
            default => $status->value,
        };
    }

    /**
     * @deprecated Use workStatusLabel() instead.
     */
    public function getWorkStatusLabel(): string
    {
        return $this->workStatusLabel();
    }

    #[Override]
    protected static function booted(): void
    {
        static::saved(function (OrderItem $item) {
            if ($item->wasChanged(WorkStatus::class)) {
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

    protected function casts(): array
    {
        return [
            'customization_json' => 'array',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }
}
