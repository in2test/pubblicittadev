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
        Schema::table('media', function (Blueprint $table) {
            $table->json('manipulations')->nullable()->change();
            $table->json('custom_properties')->nullable()->change();
            $table->json('generated_conversions')->nullable()->change();
            $table->json('responsive_images')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->json('manipulations')->nullable(false)->change();
            $table->json('custom_properties')->nullable(false)->change();
            $table->json('generated_conversions')->nullable(false)->change();
            $table->json('responsive_images')->nullable(false)->change();
        });
    }
};
