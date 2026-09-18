<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\OrderStatusChangedNotification;
use App\Models\Order;
use App\Models\User;
/**
 * OrderNotificationService handles email notifications for order status changes.
 *
 * This service encapsulates the logic for sending emails when order statuses change,
 * separating side effects from model events and domain logic.
 */
use Illuminate\Support\Facades\Mail;

class OrderNotificationService
{
    /**
     * Send notification emails when an order's payment or work status changes.
     *
     * Skips sending to the customer if the status just became 'paid' since
     * that is handled by the payment completion service.
     *
     * @param  Order  $order  The order whose status has changed.
     */
    public function sendStatusChangeNotification(Order $order): void
    {
        // Skip generic update email if just marked as paid (handled by completePayment)
        if ($order->wasChanged('payment_status') && $order->payment_status === 'paid') {
            return;
        }

        // Load items and their products for email content
        $order->loadMissing('items.product');

        // Send to customer and admins using fully qualified class names
        Mail::to($order->user)->send(new OrderStatusChangedNotification($order));

        // Notify all administrators
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Mail::to($admin)->send(new OrderStatusChangedNotification($order));
        }
    }
}
