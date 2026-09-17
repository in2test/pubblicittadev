<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\OrderPaymentNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use UnexpectedValueException;

class WebhookController extends Controller
{
    public function __construct(
        private readonly OrderPaymentNotificationService $paymentNotificationService,
    ) {}

    /**
     * Handle incoming Stripe webhook events.
     *
     * Validates the webhook signature securely and dispatches the event
     * to the appropriate handler method based on the event type.
     */
    public function handle(Request $request): JsonResponse
    {
        Stripe::setApiKey(config('stripe.secret'));
        Stripe::setApiVersion(config('stripe.api_version'));

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature') ?? '',
                config('stripe.webhook_secret') ?? ''
            );
        } catch (UnexpectedValueException $e) {
            Log::error('Stripe Webhook: Invalid payload', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe Webhook: Invalid signature. Check STRIPE_WEBHOOK_SECRET.', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('Stripe Webhook received', ['type' => $event->type]);

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event->data->object),
            default => null,
        };

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle webhook event for checkout session completion (payment).
     *
     * @param  mixed  $session  The Stripe session object.
     */
    protected function handleCheckoutSessionCompleted($session): void
    {
        /** @var Session $session */
        $orderId = $session->metadata->order_id ?? null;

        if (! $orderId) {
            Log::error('Stripe Webhook: order_id not found in session metadata', ['session' => $session->id]);

            return;
        }

        /** @var Order|null $order */
        $order = Order::find($orderId);

        if (! $order) {
            Log::error('Stripe Webhook: Order not found', ['order_id' => $orderId]);

            return;
        }

        if ($order->payment_status === PaymentStatus::Paid) {
            return;
        }

        /** @var string $paymentIntent */
        $paymentIntent = $session->payment_intent;
        $order->completePayment($paymentIntent);

        // Send payment notifications after the transaction commits
        $this->paymentNotificationService->sendAllPaymentNotifications($order);

        Log::info('Stripe Webhook: Order marked as paid and inventory decremented', ['order_id' => $orderId, 'stripe_session' => $session->id]);
    }
}
