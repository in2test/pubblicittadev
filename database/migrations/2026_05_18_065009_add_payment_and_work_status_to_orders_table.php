<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status')->default('pending')->after('status');
            $table->string('work_status')->default('pending')->after('payment_status');
        });

        // Migrate existing order statuses
        DB::table('orders')->get()->each(function ($order) {
            $paymentStatus = 'pending';
            $workStatus = 'pending';

            if ($order->status === 'paid') {
                $paymentStatus = 'paid';
            } elseif ($order->status === 'shipped') {
                $paymentStatus = 'paid';
                $workStatus = 'shipped';
            } elseif ($order->status === 'cancelled') {
                $paymentStatus = 'cancelled';
            }

            DB::table('orders')->where('id', $order->id)->update([
                'payment_status' => $paymentStatus,
                'work_status' => $workStatus,
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('id');
        });

        // Restore statuses
        DB::table('orders')->get()->each(function ($order) {
            $status = 'pending';

            if ($order->payment_status === 'cancelled') {
                $status = 'cancelled';
            } elseif ($order->work_status === 'shipped') {
                $status = 'shipped';
            } elseif ($order->payment_status === 'paid') {
                $status = 'paid';
            }

            DB::table('orders')->where('id', $order->id)->update([
                'status' => $status,
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'work_status']);
        });
    }
};
