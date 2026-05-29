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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('cached_base_price', 10, 2)->nullable()->after('offer_price');
            $table->decimal('cached_starting_price', 10, 2)->nullable()->after('cached_base_price');
            $table->decimal('cached_starting_unit_price', 10, 2)->nullable()->after('cached_starting_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'cached_base_price',
                'cached_starting_price',
                'cached_starting_unit_price',
            ]);
        });
    }
};
