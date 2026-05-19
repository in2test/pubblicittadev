<?php

declare(strict_types=1);

namespace App\Models;

use App\Mail\AdminOrderPaidNotification;
use App\Mail\OrderPaidConfirmation;
use App\Mail\OrderStatusChangedNotification;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Override;

/**
 * @property int $id
 * @property int $user_id
 * @property string $order_number
 * @property string $payment_status
 * @property string $work_status
 * @property float $total_price
 * @property int $total_items
 * @property int $shipping_address_id
 * @property int $billing_address_id
 * @property string|null $stripe_session_id
 * @property Carbon|null $paid_at
 * @property User $user
 * @property-read Collection<int, OrderItem> $items
 */
#[Fillable([
    'user_id',
    'quote_id',
    'order_number',
    'payment_status',
    'work_status',
    'total_price',
    'total_items',
    'shipping_address_id',
    'billing_address_id',
    'stripe_session_id',
    'stripe_payment_intent_id',
    'paid_at',
    'notes',
])]
class Order extends Model
{
    use HasFactory;

    protected $casts = [
        'paid_at' => 'datetime',
        'total_price' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function getPaymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'pending' => 'In Attesa',
            'paid' => 'Pagato',
            'cancelled' => 'Annullato',
            default => $this->payment_status,
        };
    }

    public function getWorkStatusLabel(): string
    {
        return match ($this->work_status) {
            'pending' => 'In Attesa',
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
        static::updated(function (Order $order) {
            if ($order->wasChanged('payment_status') || $order->wasChanged('work_status')) {
                // Do not send generic update email if it was just marked as paid, since completePayment handles its own paid confirmation emails
                if ($order->wasChanged('payment_status') && $order->payment_status === 'paid') {
                    return;
                }

                // Send email to customer
                Mail::to($order->user->email)->send(new OrderStatusChangedNotification($order));

                // Send email to all administrators
                $admins = User::where('role', '=', 'admin', 'and')->get();
                foreach ($admins as $admin) {
                    Mail::to($admin->email)->send(new OrderStatusChangedNotification($order));
                }
            }
        });
    }

    /**
     * Complete the payment process for this order.
     */
    public function completePayment(string $paymentIntentId): void
    {
        if ($this->payment_status === 'paid') {
            return;
        }

        $this->update([
            'payment_status' => 'paid',
            'stripe_payment_intent_id' => $paymentIntentId,
            'paid_at' => now(),
        ]);

        $this->decrementInventory();

        /** @var User $user */
        $user = $this->user;
        Mail::to($user->email)->send(new OrderPaidConfirmation($this));

        // Notify all administrators of the new paid order
        $admins = User::where('role', '=', 'admin', 'and')->get();
        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new AdminOrderPaidNotification($this));
        }
    }

    /**
     * Decrement the inventory for all items in the order.
     */
    protected function decrementInventory(): void
    {
        /** @var OrderItem $item */
        foreach ($this->items as $item) {
            $config = $item->customization_json;
            $productId = $item->product_id;
            $quantities = $config['quantities'] ?? [];

            if (empty($quantities)) {
                // Fallback for single quantity if quantities array is missing
                $sku = ProductSku::where('product_id', $productId)->first();
                if ($sku !== null) {
                    $sku->decrement('quantity', (int) ($config['quantity'] ?? 1));
                }

                continue;
            }

            foreach ($quantities as $skuId => $qty) {
                $sku = ProductSku::find((int) $skuId);
                if ($sku !== null) {
                    $sku->decrement('quantity', (int) $qty);
                }
            }
        }
    }
}
