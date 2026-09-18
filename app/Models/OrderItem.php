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
 * @property string|null $work_status
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

    /**
     * Get the Italian label for this order item's work status.
     *
     * @return string The Italian label (e.g., 'In Attesa', 'Completato')
     */
    public function workStatusLabel(): string
    {
        if (empty($this->work_status)) {
            return '';
        }

        return WorkStatus::from($this->work_status)->label();
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
            if ($item->wasChanged('work_status')) {
                /** @var Order $order */
                $order = $item->order;
                $order->updateWorkStatusFromItems();
            }
        });

        static::deleted(function (OrderItem $item) {
            /** @var Order $order */
            $order = $item->order;
            $order->updateWorkStatusFromItems();
        });
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'customization_json' => 'array',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            // Cast work_status to string for DB storage after enum conversion
            'work_status' => 'string',
        ];
    }
}
