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
        Schema::table('product_variation_types', function (Blueprint $table) {
            $table->boolean('is_modifier')->default(false)->after('affects_price');
        });

        Schema::table('product_variation_options', function (Blueprint $table) {
            $table->string('modifier_type')->default('flat')->after('variation_option_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variation_options', function (Blueprint $table) {
            $table->dropColumn('modifier_type');
        });

        Schema::table('product_variation_types', function (Blueprint $table) {
            $table->dropColumn('is_modifier');
        });
    }
};
