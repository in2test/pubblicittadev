<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderUpdated;
use App\Models\Order;
use App\Mail\OrderStatusChangedNotification;
use Illuminate\Support\Facades\Mail;

class SendOrderStatusChangedNotificationListener
{
    /**
     * Handle the event.
     *
     * @param  OrderUpdated  $event
     * @return void
     */
    public function handle(OrderUpdated $event): void
    {
        $order = $event->order;

        // In the updated model, we added a guard to prevent duplication for 'paid' status,
        // but we still need to dispatch the general notification for other status changes.

        // Notify all administrators of the status change
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Mail::to($admin)->send(new OrderStatusChangedNotification($order));
        }
    }
}