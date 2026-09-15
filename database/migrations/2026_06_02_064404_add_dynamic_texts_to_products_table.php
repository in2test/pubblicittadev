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
            $table->text('certifications')->nullable();
            $table->text('technical_specs')->nullable();
            $table->text('construction_features')->nullable();
            $table->text('customization_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'certifications',
                'technical_specs',
                'construction_features',
                'customization_notes',
            ]);
        });
    }
};
