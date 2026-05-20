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
            'awaiting_file' => 'Attendiamo File',
            'processing' => 'In Lavorazione',
            'ready' => 'Pronto per Spedizione',
            'shipped' => 'Spedito',
            'completed' => 'Completato',
            default => $this->work_status,
        };
    }

    /**
     * Ricalcola e aggiorna automaticamente lo stato lavorazione globale dell'ordine
     * in base allo stato dei singoli articoli.
     * Lo stato dell'ordine sarà sempre uguale allo stato di livello "più basso" tra i suoi articoli,
     * garantendo che l'ordine non risulti completato se c'è ancora un lavoro in sospeso.
     */
    public function updateWorkStatusFromItems(): void
    {
        // Se non ci sono articoli, interrompe l'esecuzione.
        if ($this->items()->count() === 0) {
            return;
        }

        // Assegniamo un "peso" numerico ad ogni stato per poterli confrontare.
        // I numeri più bassi indicano stati precedenti nel flusso di lavoro.
        $weights = [
            'pending' => 0,
            'awaiting_file' => 1,
            'processing' => 2,
            'ready' => 3,
            'shipped' => 4,
            'completed' => 5,
        ];

        $lowestWeight = 999;
        $lowestStatus = 'pending';

        // Troviamo lo stato con il peso più basso tra tutti gli articoli.
        foreach ($this->items as $item) {
            $weight = $weights[$item->work_status] ?? 0;
            if ($weight < $lowestWeight) {
                $lowestWeight = $weight;
                $lowestStatus = $item->work_status;
            }
        }

        // Aggiorna lo stato solo se è diverso da quello attuale, per evitare query inutili.
        if ($this->work_status !== $lowestStatus) {
            // Utilizziamo updateQuietly per evitare di lanciare l'evento "updated"
            // che scatenerebbe l'invio di email o cicli infiniti.
            $this->updateQuietly(['work_status' => $lowestStatus]);
        }
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

                $order->loadMissing('items.product');

                // Send email to customer
                Mail::to($order->user)->send(new OrderStatusChangedNotification($order));

                // Send email to all administrators
                $admins = User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    Mail::to($admin)->send(new OrderStatusChangedNotification($order));
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

        // Aggiorna lo stato lavorazione degli articoli.
        // Gli articoli "neutri" che erano "in attesa" (pending) passano in lavorazione (processing).
        // Gli articoli personalizzati ("awaiting_file") rimangono tali in attesa dei file del cliente.
        foreach ($this->items as $item) {
            if ($item->work_status === 'pending') {
                $item->update(['work_status' => 'processing']);
            }
        }

        $this->decrementInventory();

        $this->loadMissing('items.product');

        Mail::to($this->user)->send(new OrderPaidConfirmation($this));

        // Notify all administrators of the new paid order
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Mail::to($admin)->send(new AdminOrderPaidNotification($this));
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
                ProductSku::where('product_id', $productId)
                    ->first()
                    ?->decrement('quantity', (int) ($config['quantity'] ?? 1));

                continue;
            }

            foreach ($quantities as $skuId => $qty) {
                ProductSku::find((int) $skuId)?->decrement('quantity', (int) $qty);
            }
        }
    }
}
