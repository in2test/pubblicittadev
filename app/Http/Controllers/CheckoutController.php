<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Services\CartManager;
use App\Services\CheckoutOrderService;
use App\Services\OrderNotificationService as OrderNotificationServiceAlias;
use App\Services\StripeCheckoutSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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
    public function __construct(
        protected CartManager $cartManager,
        protected OrderNotificationServiceAlias $notificationService,
    ) {}

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
    public function createSession(
        Request $request,
        CheckoutOrderService $checkoutOrderService,
        StripeCheckoutSessionService $stripeCheckoutSessionService,
    ): RedirectResponse {
        $order = $this->orderForCheckout($request, $checkoutOrderService);
        if ($order instanceof RedirectResponse) {
            return $order;
        }

        if ($order->payment_status === 'quotation') {
            $this->cartManager->clear();

            return redirect()->route('checkout.success')
                ->with('success', 'La tua richiesta di preventivo è stata inviata con successo.')
                ->with('order_id', $order->id);
        }

        /** @var User $user */
        $user = $request->user();

        return $stripeCheckoutSessionService->redirect($order, $user);
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
    public function requestQuotation(Request $request, CheckoutOrderService $checkoutOrderService): RedirectResponse
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

        $order = $checkoutOrderService->createFromCart(
            $request,
            $items,
            $defaultShipping?->id,
            $defaultBilling?->id,
            true,
        );
        $order->load('items.product');

        // Delegate email notifications to the service class
        $this->notificationService->sendStatusChangeNotification($order);

        $this->cartManager->clear();

        return redirect()->route('checkout.success')
            ->with('success', 'La tua richiesta di preventivo è stata inviata con successo.')
            ->with('order_id', $order->id);
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

        $order = null;
        if ($request->filled('session_id')) {
            $order = Order::with(['items.product', 'shippingAddress', 'user'])
                ->where('stripe_session_id', $request->query('session_id'))
                ->first();
        } elseif (session()->has('order_id')) {
            $order = Order::with(['items.product', 'shippingAddress', 'user'])
                ->find(session('order_id'));
        } elseif ($request->user()) {
            $order = $request->user()->orders()
                ->with(['items.product', 'shippingAddress', 'user'])
                ->latest('id')
                ->first();
        }

        return view('checkout.success', [
            'isQuotation' => $isQuotation,
            'order' => $order,
        ]);
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

    private function orderForCheckout(Request $request, CheckoutOrderService $checkoutOrderService): Order|RedirectResponse
    {
        if ($request->has('order_id')) {
            /** @var Order $order */
            $order = Order::with('items.product')->findOrFail($request->input('order_id'));

            /** @var User $user */
            $user = $request->user();

            if ($order->user_id !== $user->id) {
                abort(403);
            }

            if ($order->payment_status !== 'pending') {
                return redirect()->route('dashboard.orders')->with('error', 'Questo ordine è già stato elaborato.');
            }

            return $order;
        }

        $items = $this->cartManager->getItems();
        if ($items === []) {
            return redirect()->route('cart')->with('error', 'Il tuo carrello è vuoto.');
        }

        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'shipping_method' => 'required|in:delivery,pickup',
            'shipping_address_id' => [
                'required_if:shipping_method,delivery',
                'nullable',
                Rule::exists('addresses', 'id')->where('user_id', $user->id),
            ],
            'billing_address_id' => [
                'required',
                Rule::exists('addresses', 'id')->where('user_id', $user->id),
            ],
        ]);

        $order = $checkoutOrderService->createFromCart(
            $request,
            $items,
            null,
            null,
            $request->input('payment_method') === 'quotation',
        );

        $order->load('items.product');
        $this->notificationService->sendStatusChangeNotification($order);
        $order->loadMissing('items.product');

        return $order;
    }
}
