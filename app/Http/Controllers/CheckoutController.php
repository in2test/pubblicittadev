<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\OrderPlacedNotification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShippingTier;
use App\Models\User;
use App\Services\CartManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Stripe\Checkout\Session;
use Stripe\Stripe;

/**
 * Handles the checkout process, order creation, Stripe payment session generation,
 * and quotation requests for custom apparel.
 */
class CheckoutController extends Controller
{
    /**
     * Create a new CheckoutController instance.
     *
     * @param  CartManager  $cartManager  The cart manager to retrieve cart items.
     */
    public function __construct(protected CartManager $cartManager) {}

    /**
     * Create a Stripe Checkout session for a new or existing order.
     *
     * This method handles both "pay later" flows for existing orders and
     * generating an entirely new order from the current cart. It determines
     * whether to start a Stripe payment or convert the cart to a quotation.
     *
     * @param  Request  $request  The incoming HTTP request containing payment and address details.
     * @return RedirectResponse Redirects to either the Stripe Checkout URL, the success page, or the cart on error.
     */
    public function createSession(Request $request): RedirectResponse
    {
        if ($request->has('order_id')) {
            // If 'order_id' is provided, we're resuming payment for an existing pending order
            /** @var Order $order */
            $order = Order::with('items.product')->findOrFail($request->input('order_id'));

            /** @var User $user */
            $user = $request->user();

            // Ensure the order belongs to the currently authenticated user
            if ($order->user_id !== $user->id) {
                abort(403);
            }

            // Only pending orders can be paid for
            if ($order->payment_status !== 'pending') {
                return redirect()->route('dashboard.orders')->with('error', 'Questo ordine è già stato elaborato.');
            }
        } else {
            // Otherwise, create a new order directly from the cart
            $items = $this->cartManager->getItems();

            if ($items === []) {
                return redirect()->route('cart')->with('error', 'Il tuo carrello è vuoto.');
            }

            $request->validate([
                'shipping_method' => 'required|in:delivery,pickup',
                'shipping_address_id' => 'required_if:shipping_method,delivery|nullable|exists:addresses,id',
                'billing_address_id' => 'required|exists:addresses,id',
            ]);

            // Determine if the user has requested a quotation instead of an immediate payment
            $isQuotation = $request->input('payment_method') === 'quotation';
            $order = $this->createOrderFromCart($request, $items, null, null, $isQuotation);

            $order->load('items.product');

            /** @var User $user */
            $user = $request->user();

            // Notify the customer and admin about the new order/quotation via email
            Mail::to($user)->send(new OrderPlacedNotification($order));
            Mail::to(User::where('role', 'admin')->get())->send(new OrderPlacedNotification($order));
        }

        $order->loadMissing('items.product');

        // If it's a quotation, clear the cart and immediately redirect to success without involving Stripe
        if ($order->payment_status === 'quotation') {
            $this->cartManager->clear();

            return redirect()->route('checkout.success')->with('success', 'La tua richiesta di preventivo è stata inviata con successo.');
        }

        // Set up Stripe API keys
        Stripe::setApiKey(config('stripe.secret'));
        Stripe::setApiVersion(config('stripe.api_version'));

        // Build Stripe line items based on the order's items
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

        // Add shipping cost as a separate line item if applicable
        if ($order->shipping_cost > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Costo di Spedizione',
                    ],
                    'unit_amount' => (int) round($order->shipping_cost * 100),
                ],
                'quantity' => 1,
            ];
        }

        /** @var User $user */
        $user = $request->user();

        // Create the Stripe Checkout session with the necessary metadata
        $session = Session::create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancel'),
            'customer_email' => $user->email,
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
        ]);

        // Save the Stripe session ID to the order for tracking
        $order->update(['stripe_session_id' => $session->id]);

        // Redirect the user to the Stripe hosted checkout page
        return redirect()->away($session->url ?? '');
    }

    /**
     * Process a direct request for a quotation from the user's cart.
     *
     * This skips the standard checkout and payment flow, instead creating
     * an order marked as 'quotation' and notifying administrators.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @return RedirectResponse Redirects to the success page on completion.
     */
    public function requestQuotation(Request $request): RedirectResponse
    {
        $items = $this->cartManager->getItems();

        if ($items === []) {
            return redirect()->route('cart')->with('error', 'Il tuo carrello è vuoto.');
        }

        /** @var User $user */
        $user = $request->user();

        // Resolving default addresses if they exist
        $defaultShipping = $user->addresses()->where('is_default', true)->first()
            ?? $user->addresses()->first();
        $defaultBilling = $defaultShipping; // fallback

        $order = $this->createOrderFromCart($request, $items, $defaultShipping?->id, $defaultBilling?->id, true);
        $order->load('items.product');

        Mail::to($user)->send(new OrderPlacedNotification($order));
        Mail::to(User::where('role', 'admin')->get())->send(new OrderPlacedNotification($order));

        $this->cartManager->clear();

        return redirect()->route('checkout.success')->with('success', 'La tua richiesta di preventivo è stata inviata con successo.');
    }

    /**
     * Display the checkout success page.
     *
     * Clears the cart and determines whether the successful action was
     * a completed payment or a quotation request.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @return View The rendered success view.
     */
    public function success(Request $request): View
    {
        $this->cartManager->clear();

        $isQuotation = session()->has('success') && str_contains(
            (string) session('success', ''),
            'preventivo'
        );

        return view('checkout.success', ['isQuotation' => $isQuotation]);
    }

    /**
     * Display the checkout cancellation page.
     *
     * This is the return URL if the user aborts the Stripe Checkout process.
     *
     * @return View The rendered cancel view.
     */
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
        $productIds = collect($items)->pluck('product_id')->filter()->unique();
        $products = $productIds->isEmpty()
            ? collect()
            : Product::with(['variationTypes', 'skus.options', 'pricingTiers', 'media'])->whereIn('id', $productIds)->get()->keyBy('id');

        /** @var User $user */
        $user = $request->user();

        // Calculate the total item cost and determine shipping method
        $itemsTotal = $this->cartManager->total();
        $shippingMethod = $request->input('shipping_method', 'delivery');
        $shippingCost = 0.00;

        // Calculate shipping costs dynamically based on configured shipping tiers for 'delivery'
        if ($shippingMethod === 'delivery') {
            $tier = ShippingTier::where('min_order_total', '<=', $itemsTotal)
                ->orderBy('min_order_total', 'desc')
                ->first();
            if ($tier) {
                $shippingCost = (float) $tier->shipping_cost;
            }
        }

        // Calculate the grand total
        $totalPrice = $itemsTotal + $shippingCost;

        // Create the primary order record
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-'.strtoupper(str_replace('.', '', uniqid('', true))),
            'payment_status' => $isQuotation ? 'quotation' : 'pending',
            'work_status' => 'pending',
            'items_total' => $itemsTotal,
            'shipping_cost' => $shippingCost,
            'shipping_method' => $shippingMethod,
            'total_price' => $totalPrice,
            'total_items' => $this->cartManager->count(),
            'shipping_address_id' => $shippingId ?? $request->input('shipping_address_id'),
            'billing_address_id' => $billingId ?? $request->input('billing_address_id'),
            'notes' => ($shippingMethod === 'pickup' ? "[Ritiro in negozio]\n" : '').$request->input('notes'),
        ]);

        // Iterate over each cart item to calculate its final price and create the OrderItem record
        foreach ($items as $item) {
            if (! $product = $products->get((int) $item['product_id'])) {
                continue;
            }

            $qty = $this->cartManager->getItemQuantity($item);

            // Calculate the total price of this specific item line based on variations, quantity, and dimensions
            $totalPrice = $product->calculateTotalPrice(
                $qty,
                $item['quantities'] ?? [],
                isset($item['width']) ? (float) $item['width'] : null,
                isset($item['height']) ? (float) $item['height'] : null,
                $item['selected_options'] ?? []
            );

            // Determine the unit price
            $unitPrice = $qty > 0 ? $totalPrice / $qty : 0.0;

            // Check if the item has personalization options (modifiers or design files) to set its initial work status
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

            // Create the corresponding OrderItem
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
