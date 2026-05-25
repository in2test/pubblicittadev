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
        Schema::table('pricing_tiers', function (Blueprint $table) {
            $table->foreignId('product_sku_id')->nullable()->after('product_id')->constrained('product_skus')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_tiers', function (Blueprint $table) {
            $table->dropForeign(['product_sku_id']);
            $table->dropColumn('product_sku_id');
        });
    }
};
