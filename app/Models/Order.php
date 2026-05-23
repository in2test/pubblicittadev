<?php

declare(strict_types=1);

namespace App\Models;

use App\Mail\AdminOrderPaidNotification;
use App\Mail\OrderPaidConfirmation;
use App\Mail\OrderStatusChangedNotification;
use Carbon\CarbonImmutable;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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

 * @property string|null $stripe_payment_intent_id
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Address|null $billingAddress
 * @property-read int|null $items_count

 * @property-read Address|null $shippingAddress
 *
 * @method static OrderFactory factory($count = null, $state = [])
 * @method static Builder<static>|Order newModelQuery()
 * @method static Builder<static>|Order newQuery()
 * @method static Builder<static>|Order query()
 * @method static Builder<static>|Order whereBillingAddressId($value)
 * @method static Builder<static>|Order whereCreatedAt($value)
 * @method static Builder<static>|Order whereId($value)
 * @method static Builder<static>|Order whereNotes($value)
 * @method static Builder<static>|Order whereOrderNumber($value)
 * @method static Builder<static>|Order wherePaidAt($value)
 * @method static Builder<static>|Order wherePaymentStatus($value)
 * @method static Builder<static>|Order whereShippingAddressId($value)
 * @method static Builder<static>|Order whereStripePaymentIntentId($value)
 * @method static Builder<static>|Order whereStripeSessionId($value)
 * @method static Builder<static>|Order whereTotalItems($value)
 * @method static Builder<static>|Order whereTotalPrice($value)
 * @method static Builder<static>|Order whereUpdatedAt($value)
 * @method static Builder<static>|Order whereUserId($value)
 * @method static Builder<static>|Order whereWorkStatus($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'user_id',

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

    /**
     * Ottiene l'utente che ha effettuato questo ordine.
     *
     * @return BelongsTo<User, static>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Ottiene tutti gli articoli (righe) associati a questo ordine.
     *
     * @return HasMany<OrderItem, static>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Ottiene l'indirizzo di spedizione utilizzato per questo ordine.
     *
     * @return BelongsTo<Address, static>
     */
    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    /**
     * Ottiene l'indirizzo di fatturazione utilizzato per questo ordine.
     *
     * @return BelongsTo<Address, static>
     */
    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    /**
     * Restituisce un'etichetta descrittiva e tradotta per lo stato del pagamento.
     */
    public function getPaymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'pending' => 'In Attesa',
            'paid' => 'Pagato',
            'cancelled' => 'Annullato',
            default => $this->payment_status,
        };
    }

    /**
     * Restituisce un'etichetta descrittiva e tradotta per lo stato di lavorazione.
     */
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
     * Completa il processo di pagamento per questo ordine.
     * Segna l'ordine come pagato, avanza lo stato di lavorazione degli articoli,
     * scala l'inventario ed invia le notifiche email.
     *
     * @param  string  $paymentIntentId  ID del Payment Intent di Stripe
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

        // Notifica tutti gli amministratori del nuovo ordine pagato
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Mail::to($admin)->send(new AdminOrderPaidNotification($this));
        }
    }

    /**
     * Decrementa le giacenze di magazzino per tutti gli articoli presenti nell'ordine.
     * Questo metodo considera sia le quantità singole che gli array di quantità.
     */
    protected function decrementInventory(): void
    {
        /** @var OrderItem $item */
        foreach ($this->items as $item) {
            $config = $item->customization_json;
            $productId = $item->product_id;
            $quantities = $config['quantities'] ?? [];

            if (empty($quantities)) {
                // Fallback nel caso di singola quantità (senza array quantities)
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
