<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\AdminOrderPaidNotification;
use App\Mail\OrderPaidConfirmation;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * OrderPaymentNotificationService handles email notifications for order payment completion.
 *
 * This service encapsulates the logic for sending emails after successful payment,
 * separating side effects from model methods and domain logic.
 */
class OrderPaymentNotificationService
{
    /**
     * Send confirmation email to customer after payment is completed.
     *
     * @param  Order  $order  The paid order.
     */
    public function sendPaidConfirmation(Order $order): void
    {
        $order->loadMissing('items.product');
        Mail::to($order->user)->send(new OrderPaidConfirmation($order));
    }

    /**
     * Send notification emails to all administrators after payment is completed.
     *
     * @param  Order  $order  The paid order.
     */
    public function sendAdminPaidNotification(Order $order): void
    {
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Mail::to($admin)->send(new AdminOrderPaidNotification($order));
        }
    }

    /**
     * Complete payment notifications after order has been marked as paid.
     * This method sends both customer confirmation and admin notifications.
     *
     * @param  Order  $order  The order that was just paid.
     */
    public function sendAllPaymentNotifications(Order $order): void
    {
        $this->sendPaidConfirmation($order);
        $this->sendAdminPaidNotification($order);
    }
}
