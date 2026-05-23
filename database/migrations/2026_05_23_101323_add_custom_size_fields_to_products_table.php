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
            $table->decimal('sheet_width', 8, 2)->nullable();
            $table->decimal('sheet_height', 8, 2)->nullable();
            $table->boolean('allows_custom_size')->default(false);
            $table->decimal('min_custom_width', 8, 2)->nullable();
            $table->decimal('max_custom_width', 8, 2)->nullable();
            $table->decimal('min_custom_height', 8, 2)->nullable();
            $table->decimal('max_custom_height', 8, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'sheet_width',
                'sheet_height',
                'allows_custom_size',
                'min_custom_width',
                'max_custom_width',
                'min_custom_height',
                'max_custom_height',
            ]);
        });
    }
};
