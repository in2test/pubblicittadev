<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\WorkStatus;
use App\Services\OrderNotificationService;
use App\Services\OrderPaymentNotificationService;
use Carbon\CarbonImmutable;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Override;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Class Order
 *
 * Represents a customer's order for apparel or custom items.
 *
 * Workflow states for `work_status`:
 * - pending: Initial state, order is created but not yet being worked on.
 * - awaiting_file: Order is waiting for the customer to upload required design files.
 * - processing: The items in the order are currently being manufactured or prepared.
 * - ready: The order is complete and ready to be shipped.
 * - shipped: The order has been handed over to the transporter.
 * - completed: The order has been delivered and finalized.
 *
 * Workflow states for `payment_status`:
 * - pending: Payment has not yet been received or confirmed.
 * - paid: Payment has been successfully processed.
 * - cancelled: Payment was cancelled or failed.
 * - quotation: The order is a quote and has not been paid yet.
 *
 * @property int $id
 * @property int $user_id
 * @property string $order_number
 * @property PaymentStatus $payment_status
 * @property WorkStatus $work_status
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
    PaymentStatus::class,
    WorkStatus::class,
    'total_price',
    'items_total',
    'shipping_cost',
    'shipping_method',
    'total_items',
    'shipping_address_id',
    'billing_address_id',
    'stripe_session_id',
    'stripe_payment_intent_id',
    'paid_at',
    'notes',
    'transporter_id',
    'tracking_code',
    'tracking_url',
])]
/**
 * @use HasFactory<OrderFactory>
 */
class Order extends Model implements HasMedia
{
    /**
     * @use HasFactory<OrderFactory>
     */
    use HasFactory;

    use InteractsWithMedia;

    /**
     * Relationship: Order -> User.
     * Get the customer who placed this order.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Order -> OrderItems.
     * Get all products/items associated with this order.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relationship: Order -> Address (Shipping).
     * Get the address where the order should be delivered.
     *
     * @return BelongsTo<Address, $this>
     */
    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    /**
     * Relationship: Order -> Address (Billing).
     * Get the address used for invoicing.
     *
     * @return BelongsTo<Address, $this>
     */
    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    /**
     * Relationship: Order -> Transporter
     *
     * @return BelongsTo<Transporter, $this>
     */
    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Transporter::class);
    }

    /**
     * Get the full tracking URL if transporter and tracking code are set.
     *
     * Replaces the '{tracking_code}' placeholder in the transporter's URL template
     * with the actual tracking code for this order.
     *
     * @return Attribute<string|null, never>
     */
    protected function trackingUrl(): Attribute
    {
        return Attribute::make(get: function () {
            if (! empty($this->tracking_url)) {
                return $this->tracking_url;
            }
            if (! $this->transporter_id || ! $this->tracking_code || ! $this->transporter) {
                return null;
            }
            $template = $this->transporter->tracking_url_template;
            if (! $template) {
                return null;
            }

            return str_replace('{tracking_code}', $this->tracking_code, $template);
        });
    }

    /**
     * @property PaymentStatus $payment_status
     */
    public function paymentStatusLabel(): string
    {
        // Handle both enum instances and raw string values (for backward compatibility)
        if ($this->payment_status instanceof UnitEnum) {
            return match ($this->payment_status) {
                PaymentStatus::Pending => 'In Attesa',
                PaymentStatus::Paid => 'Pagato',
                PaymentStatus::Cancelled => 'Annullato',
                PaymentStatus::Quotation => 'Preventivo',
            };
        }

        // Fallback for raw string values (old data or migrations)
        return match ($this->payment_status) {
            'pending' => 'In Attesa',
            'paid' => 'Pagato',
            'cancelled' => 'Annullato',
            'quotation' => 'Preventivo',
            default => (string) $this->payment_status,
        };
    }

    /**
     * @deprecated Use paymentStatusLabel() instead.
     */
    public function getPaymentStatusLabel(): string
    {
        return $this->paymentStatusLabel();
    }

    /**
     * @property WorkStatus $work_status
     */
    public function workStatusLabel(): string
    {
        // Handle both enum instances and raw string values (for backward compatibility)
        if ($this->work_status instanceof UnitEnum) {
            return match ($this->work_status) {
                WorkStatus::Pending => 'In Attesa',
                WorkStatus::AwaitingFile => 'Attendiamo File',
                WorkStatus::Processing => 'In Lavorazione',
                WorkStatus::Ready => 'Pronto per Spedizione',
                WorkStatus::Shipped => 'Spedito',
                WorkStatus::Completed => 'Completato',
            };
        }

        // Fallback for raw string values (old data or migrations)
        return match ($this->work_status) {
            'pending' => 'In Attesa',
            'awaiting_file' => 'Attendiamo File',
            'processing' => 'In Lavorazione',
            'ready' => 'Pronto per Spedizione',
            'shipped' => 'Spedito',
            'completed' => 'Completato',
            default => (string) $this->work_status,
        };
    }

    /**
     * @deprecated Use workStatusLabel() instead.
     */
    public function getWorkStatusLabel(): string
    {
        return $this->workStatusLabel();
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
        // Usiamo i valori delle stringhe come chiave dell'array.
        $weights = [
            WorkStatus::Pending->value => 0,
            WorkStatus::AwaitingFile->value => 1,
            WorkStatus::Processing->value => 2,
            WorkStatus::Ready->value => 3,
            WorkStatus::Shipped->value => 4,
            WorkStatus::Completed->value => 5,
        ];

        $lowestWeight = 999;
        $lowestStatus = WorkStatus::Pending;

        // Troviamo lo stato con il peso più basso tra tutti gli articoli.
        foreach ($this->items as $item) {
            $weight = $weights[$item->work_status->value] ?? 0;
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

    /**
     * Bootstrap the model and its traits.
     *
     * Registers a listener to send notifications when order statuses change.
     * The notification logic is delegated to OrderNotificationService,
     * keeping side effects out of the domain model.
     */
    #[Override]
    protected static function booted(): void
    {
        // Use container resolution to get the service for email sending
        // This keeps the model clean while still triggering notifications synchronously
        // (queues are unavailable, so we defer() by calling sync methods)
        static::updated(function (Order $order): void {
            if ($order->wasChanged('payment_status') || $order->wasChanged('work_status')) {
                // Skip email if just marked as paid (handled by completePayment notifications)
                if ($order->wasChanged('payment_status') && $order->payment_status === PaymentStatus::Paid) {
                    return;
                }

                $order->loadMissing('items.product');

                // Delegate to service for email sending
                // Order status changes (except when marked as paid) trigger notification emails
                app(OrderNotificationService::class)->sendStatusChangeNotification($order);
            }
        });
    }

    /**
     * Completa il processo di pagamento per questo ordine.
     * Segna l'ordine come pagato, avanza lo stato di lavorazione degli articoli,
     * e scala l'inventario. Le notifiche email sono delegate al servizio
     * OrderPaymentNotificationService.
     *
     * @param  string  $paymentIntentId  ID del Payment Intent di Stripe
     */
    public function completePayment(string $paymentIntentId): void
    {
        /** @var Order|null $paidOrder */
        $paidOrder = DB::transaction(function () use ($paymentIntentId): ?Order {
            /** @var Order|null $order */
            $order = self::query()
                ->with('items')
                ->lockForUpdate()
                ->find($this->getKey());

            if (! $order || $order->payment_status === PaymentStatus::Paid) {
                return null;
            }

            $order->update([
                'payment_status' => PaymentStatus::Paid,
                'stripe_payment_intent_id' => $paymentIntentId,
                'paid_at' => now(),
            ]);

            // Advance non-personalized items while preserving items waiting for customer files.
            foreach ($order->items as $item) {
                if ($item->work_status === WorkStatus::Pending) {
                    $item->update(['work_status' => WorkStatus::Processing]);
                }
            }

            $order->decrementInventory();

            return $order;
        });

        // Only send notifications if payment was actually successful (order exists and wasn't already paid)
        if ($paidOrder instanceof Order) {
            // Send payment notifications after the transaction commits
            // Use container resolution to get the service - keeps model clean while supporting direct calls
            app(OrderPaymentNotificationService::class)->sendAllPaymentNotifications($paidOrder);
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
            /** @var array{quantity?: int|string, quantities?: array<int|string, int|string>} $config */
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

    /**
     * The attributes that should be cast to native types.
     *
     * Ensures total_price is always treated as a decimal and paid_at as a Carbon instance.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'total_price' => 'decimal:2',
        ];
    }
}
