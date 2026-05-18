<?php

declare(strict_types=1);

use App\Mail\AdminOrderPaidNotification;
use App\Mail\OrderPaidConfirmation;
use App\Mail\OrderStatusChangedNotification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('sends confirmation emails to user and admins when payment is completed', function () {
    Mail::fake();

    // Create admin users
    $admin1 = User::factory()->create(['role' => 'admin']);
    $admin2 = User::factory()->create(['role' => 'admin']);
    $regularUser = User::factory()->create(['role' => 'user']); // unrelated user

    // Create customer and order
    $customer = User::factory()->create(['role' => 'user']);
    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'payment_status' => 'pending',
        'work_status' => 'pending',
    ]);

    // Complete payment
    $order->completePayment('pi_test123');

    // Assert customer received OrderPaidConfirmation
    Mail::assertSent(OrderPaidConfirmation::class, fn ($mail) => $mail->hasTo($customer->email) && $mail->order->id === $order->id);

    // Assert all administrators received AdminOrderPaidNotification
    Mail::assertSent(AdminOrderPaidNotification::class, fn ($mail) => $mail->hasTo($admin1->email) && $mail->order->id === $order->id);

    Mail::assertSent(AdminOrderPaidNotification::class, fn ($mail) => $mail->hasTo($admin2->email) && $mail->order->id === $order->id);

    // Assert unrelated user did not receive any email
    Mail::assertNotSent(AdminOrderPaidNotification::class, fn ($mail) => $mail->hasTo($regularUser->email));

    Mail::assertNotSent(OrderPaidConfirmation::class, fn ($mail) => $mail->hasTo($regularUser->email));
});

it('sends status changed emails to user and admins when order status is updated to shipped or cancelled', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'user']);
    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'payment_status' => 'paid',
        'work_status' => 'pending',
    ]);

    // Update status to shipped
    $order->update(['work_status' => 'shipped']);

    // Assert customer received OrderStatusChangedNotification
    Mail::assertSent(OrderStatusChangedNotification::class, fn ($mail) => $mail->hasTo($customer->email) && $mail->order->id === $order->id && $mail->order->work_status === 'shipped');

    // Assert administrator received OrderStatusChangedNotification
    Mail::assertSent(OrderStatusChangedNotification::class, fn ($mail) => $mail->hasTo($admin->email) && $mail->order->id === $order->id && $mail->order->work_status === 'shipped');
});

it('does not send status changed emails when status is updated to paid to avoid duplication', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'user']);
    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'payment_status' => 'pending',
        'work_status' => 'pending',
    ]);

    // Update status to paid directly or via completePayment
    $order->update(['payment_status' => 'paid']);

    // Assert OrderStatusChangedNotification is not sent (since completePayment handles its own paid notifications)
    Mail::assertNotSent(OrderStatusChangedNotification::class);
});
