<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\OrderPlacedNotification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class CheckoutController extends Controller
{
    public function __construct(protected CartManager $cartManager) {}

    public function createSession(Request $request): RedirectResponse
    {
        if ($request->has('order_id')) {
            /** @var Order $order */
            $order = Order::with('items.product')->findOrFail($request->input('order_id'));

            if ($order->user_id !== $request->user()->id) {
                abort(403);
            }

            if ($order->payment_status !== 'pending') {
                return redirect()->route('dashboard.orders')->with('error', 'Questo ordine è già stato elaborato.');
            }
        } else {
            $items = $this->cartManager->getItems();

            if ($items === []) {
                return redirect()->route('cart')->with('error', 'Il tuo carrello è vuoto.');
            }

            $request->validate([
                'shipping_address_id' => 'required|exists:addresses,id',
                'billing_address_id' => 'required|exists:addresses,id',
            ]);

            $isQuotation = $request->input('payment_method') === 'quotation';
            $order = $this->createOrderFromCart($request, $items, null, null, $isQuotation);

            $order->load('items.product');

            Mail::to($request->user())->send(new OrderPlacedNotification($order));
            Mail::to(User::where('role', 'admin')->get())->send(new OrderPlacedNotification($order));
        }

        $order->loadMissing('items.product');

        if ($order->payment_status === 'quotation') {
            $this->cartManager->clear();

            return redirect()->route('checkout.success')->with('success', 'La tua richiesta di preventivo è stata inviata con successo.');
        }

        Stripe::setApiKey(config('stripe.secret'));
        Stripe::setApiVersion(config('stripe.api_version'));

        $lineItems = $order->items->map(function (OrderItem $item): array {
            /** @var Product|null $product */
            $product = $item->product;

            return [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $product->name ?? 'Prodotto',
                        'description' => 'Item #'.$item->id,
                    ],
                    'unit_amount' => (int) round($item->unit_price * 100),
                ],
                'quantity' => $item->quantity,
            ];
        })->toArray();

        $session = Session::create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancel'),
            'customer_email' => $request->user()->email,
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
        ]);

        $order->update(['stripe_session_id' => $session->id]);

        return redirect($session->url);
    }

    public function requestQuotation(Request $request): RedirectResponse
    {
        $items = $this->cartManager->getItems();

        if ($items === []) {
            return redirect()->route('cart')->with('error', 'Il tuo carrello è vuoto.');
        }

        // Resolving default addresses if they exist
        $defaultShipping = $request->user()->addresses()->where('is_default', true)->first()
            ?? $request->user()->addresses()->first();
        $defaultBilling = $defaultShipping; // fallback
        $order = $this->createOrderFromCart($request, $items, $defaultShipping?->id, $defaultBilling?->id, true);
        $order->load('items.product');

        Mail::to($request->user())->send(new OrderPlacedNotification($order));
        Mail::to(User::where('role', 'admin')->get())->send(new OrderPlacedNotification($order));

        $this->cartManager->clear();

        return redirect()->route('checkout.success')->with('success', 'La tua richiesta di preventivo è stata inviata con successo.');
    }

    public function success(Request $request): View
    {
        $this->cartManager->clear();

        $isQuotation = session()->has('success') && str_contains(
            (string) session('success', ''),
            'preventivo'
        );

        return view('checkout.success', ['isQuotation' => $isQuotation]);
    }

    public function cancel(): View
    {
        return view('checkout.cancel');
    }

    /**
     * Common logic to create an order and its items from the cart.
     *
     * @param  Request  $request  The current HTTP request.
     * @param  array<string, array<string, mixed>>  $items  The list of cart items to process.
     * @param  int|null  $shippingId  Optional shipping address ID.
     * @param  int|null  $billingId  Optional billing address ID.
     * @param  bool  $isQuotation  Whether the order is a quotation.
     * @return Order The newly created order.
     */
    protected function createOrderFromCart(Request $request, array $items, ?int $shippingId = null, ?int $billingId = null, bool $isQuotation = false): Order
    {
        $order = Order::create([
            'user_id' => $request->user()->id,
            'order_number' => 'ORD-'.strtoupper(str_replace('.', '', uniqid('', true))),
            'payment_status' => $isQuotation ? 'quotation' : 'pending',
            'work_status' => 'pending',
            'total_price' => $this->cartManager->total(),
            'total_items' => $this->cartManager->count(),
            'shipping_address_id' => $shippingId ?? $request->input('shipping_address_id'),
            'billing_address_id' => $billingId ?? $request->input('billing_address_id'),
            'notes' => $request->input('notes'),
        ]);

        foreach ($items as $item) {
            if (! $product = Product::find($item['product_id'])) {
                continue;
            }

            $qty = $this->cartManager->getItemQuantity($item);
            $totalPrice = $product->calculateTotalPrice(
                $qty,
                $item['quantities'] ?? [],
                isset($item['width']) ? (float) $item['width'] : null,
                isset($item['height']) ? (float) $item['height'] : null,
                $item['selected_options'] ?? []
            );
            $unitPrice = $qty > 0 ? $totalPrice / $qty : 0.0;

            $hasModifierOption = false;
            if (! empty($item['selected_options'])) {
                $modifierTypeIds = $product->variationTypes()
                    ->wherePivot('is_modifier', true)
                    ->pluck('variation_types.id')
                    ->toArray();
                foreach ($item['selected_options'] as $typeId => $optIds) {
                    if (in_array((int) $typeId, $modifierTypeIds)) {
                        $hasModifierOption = true;
                        break;
                    }
                }
            }
            $hasPersonalization = $hasModifierOption || ! empty($item['design_file_path']);
            $initialWorkStatus = $hasPersonalization ? 'awaiting_file' : 'pending';

            $order->items()->create([
                'product_id' => $product->id,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'subtotal' => $totalPrice,
                'customization_json' => $item,
                'work_status' => $initialWorkStatus,
            ]);
        }

        return $order;
    }
}
