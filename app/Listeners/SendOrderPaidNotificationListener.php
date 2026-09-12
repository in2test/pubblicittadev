<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\Order;
use App\Mail\AdminOrderPaidNotification;
use App\Mail\OrderPaidConfirmation;
use Illuminate\Support\Facades\Mail;

class SendOrderPaidNotificationListener
{
    /**
     * Handle the event.
     *
     * @param  OrderPaid  $event
     * @return void
     */
    public function handle(OrderPaid $event): void
    {
        $order = $event->order;

        // 1. Send confirmation to the user
        Mail::to($order->user)->send(new OrderPaidConfirmation($order));

        // 2. Notify all administrators
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Mail::to($admin)->send(new AdminOrderPaidNotification($order));
        }
    }
}