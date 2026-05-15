<?php

declare(strict_types=1);

namespace App\Models;

use App\Mail\OrderPaidConfirmation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

#[Fillable([
    'user_id',
    'quote_id',
    'order_number',
    'status',
    'total_price',
    'total_items',
    'shipping_address_id',
    'billing_address_id',
    'stripe_session_id',
    'stripe_payment_intent_id',
    'paid_at',
    'notes',
])]
/**
 * @property int $id
 * @property int $user_id
 * @property string $order_number
 * @property string $status
 * @property float $total_price
 * @property int $total_items
 * @property int $shipping_address_id
 * @property int $billing_address_id
 * @property string|null $stripe_session_id
 * @property Carbon|null $paid_at
 * @property User $user
 * @property-read Collection<int, OrderItem> $items
 */
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

    /**
     * Complete the payment process for this order.
     */
    public function completePayment(string $paymentIntentId): void
    {
        if ($this->status === 'paid') {
            return;
        }

        $this->update([
            'status' => 'paid',
            'stripe_payment_intent_id' => $paymentIntentId,
            'paid_at' => now(),
        ]);

        $this->decrementInventory();

        /** @var User $user */
        $user = $this->user;
        Mail::to($user->email)->send(new OrderPaidConfirmation($this));
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
            $colorId = $item->color_id;
            $quantities = $config['quantities'] ?? [];

            if (empty($quantities)) {
                // Fallback for single quantity if quantities array is missing
                $this->updateVariationQuantity($productId, $colorId, null, (int) ($config['quantity'] ?? 1));

                continue;
            }

            foreach ($quantities as $sizeId => $qty) {
                $this->updateVariationQuantity($productId, $colorId, (int) $sizeId, (int) $qty);
            }
        }
    }

    /**
     * Update the quantity for a specific variation.
     */
    protected function updateVariationQuantity(int $productId, ?int $colorId, ?int $sizeId, int $qty): void
    {
        $query = ProductVariation::where('product_id', $productId);

        if ($colorId) {
            $query->where('color_id', $colorId);
        }

        if ($sizeId) {
            $query->where('size_id', $sizeId);
        }

        // We take the first matching variation. In our system, stock is usually
        // tracked by product+color+size, ignoring placements for stock levels.
        $variation = $query->first();

        if ($variation) {
            $variation->decrement('quantity', $qty);
        }
    }
}
