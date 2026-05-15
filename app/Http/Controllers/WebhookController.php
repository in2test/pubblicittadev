<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class WebhookController extends Controller
{
    /**
     * Handle the Stripe webhook event.
     */
    public function handle(Request $request)
    {
        Stripe::setApiKey(config('stripe.secret'));

        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = config('stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $this->handleCheckoutSessionCompleted($session);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Update the order status to 'paid' when the checkout session is completed.
     */
    protected function handleCheckoutSessionCompleted($session): void
    {
        $orderId = $session->metadata->order_id ?? null;

        if (! $orderId) {
            Log::error('Stripe Webhook: order_id not found in session metadata', ['session' => $session->id]);

            return;
        }

        $order = Order::find($orderId);

        if (! $order) {
            Log::error('Stripe Webhook: Order not found', ['order_id' => $orderId]);

            return;
        }

        if ($order->status === 'paid') {
            return;
        }

        $order->update([
            'status' => 'paid',
            'stripe_payment_intent_id' => $session->payment_intent,
            'paid_at' => now(),
        ]);

        Log::info('Stripe Webhook: Order marked as paid', ['order_id' => $orderId, 'stripe_session' => $session->id]);

        // Here you can trigger order confirmation emails or other post-payment logic
    }
}
