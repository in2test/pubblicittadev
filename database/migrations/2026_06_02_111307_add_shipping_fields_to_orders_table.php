<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('items_total', 10, 2)->after('work_status')->default(0);
            $table->decimal('shipping_cost', 10, 2)->after('items_total')->default(0);
            $table->string('shipping_method')->after('shipping_cost')->default('delivery');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['items_total', 'shipping_cost', 'shipping_method']);
        });
    }
};
