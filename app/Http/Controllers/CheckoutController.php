<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\OrderPlacedNotification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class CheckoutController extends Controller
{
    public function __construct(protected CartManager $cartManager) {}

    public function createSession(Request $request)
    {
        // 1. Check if we are re-paying an existing order
        if ($request->has('order_id')) {
            /** @var Order $order */
            $order = Order::with('items.product')->findOrFail($request->input('order_id'));

            // Authorization
            if ($order->user_id !== auth()->id()) {
                abort(403);
            }

            // Only pending orders can be re-paid
            if ($order->payment_status !== 'pending') {
                return redirect()->route('dashboard.orders')->with('error', 'Questo ordine è già stato elaborato.');
            }
        } else {
            // 2. Creating a new order from cart
            $items = $this->cartManager->getItems();

            if ($items === []) {
                return redirect()->route('cart')->with('error', 'Il tuo carrello è vuoto.');
            }

            $request->validate([
                'shipping_address_id' => 'required|exists:addresses,id',
                'billing_address_id' => 'required|exists:addresses,id',
            ]);

            /** @var Order $order */
            $order = Order::create([
                'user_id' => auth()->id(),
                'order_number' => 'ORD-'.strtoupper(str_replace('.', '', uniqid('', true))),
                'payment_status' => 'pending',
                'work_status' => 'pending',
                'total_price' => $this->cartManager->total(),
                'total_items' => $this->cartManager->count(),
                'shipping_address_id' => $request->input('shipping_address_id'),
                'billing_address_id' => $request->input('billing_address_id'),
                'notes' => $request->input('notes'),
            ]);

            foreach ($items as $item) {
                /** @var Product|null $product */
                $product = Product::find($item['product_id'], ['*']);
                if (! $product) {
                    continue;
                }

                $qty = $this->cartManager->getItemQuantity($item);
                $unitPrice = $product->calculateFinalUnitPrice(
                    $qty,
                    $item['print_placements'] ?? [],
                    isset($item['print_side_id']) ? (int) $item['print_side_id'] : null,
                    isset($item['width']) ? (float) $item['width'] : null,
                    isset($item['height']) ? (float) $item['height'] : null
                );

                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $unitPrice * $qty,
                    'customization_json' => $item,
                ]);
            }

            // Load the newly created items with their products
            $order->load('items.product');

            // Send order placement confirmation email to customer
            Mail::to($order->user->email)->send(new OrderPlacedNotification($order));

            // Notify all administrators of the new pending order
            $admins = User::where('role', '=', 'admin', 'and')->get();
            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(new OrderPlacedNotification($order));
            }
        }

        // Ensure items and product relationships are fully preloaded to prevent any empty Stripe line_items exceptions
        $order->loadMissing('items.product');

        Stripe::setApiKey(config('stripe.secret'));
        Stripe::setApiVersion(config('stripe.api_version'));

        $lineItems = [];
        /** @var OrderItem $item */
        foreach ($order->items as $item) {
            /** @var Product|null $product */
            $product = $item->product;
            $lineItems[] = [
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
        }

        $session = Session::create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancel'),
            'customer_email' => auth()->user()->email,
            'metadata' => [
                'order_id' => $order->id,
            ],
        ]);

        $order->update(['stripe_session_id' => $session->id]);

        return redirect($session->url);
    }

    public function success(Request $request)
    {
        // Clear cart after success
        $this->cartManager->clear();

        return view('checkout.success');
    }

    public function cancel()
    {
        return view('checkout.cancel');
    }
}
