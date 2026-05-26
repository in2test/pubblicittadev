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
        Schema::table('variation_types', function (Blueprint $table) {
            $table->string('default_modifier_type')->nullable()->after('presentation_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('variation_types', function (Blueprint $table) {
            $table->dropColumn('default_modifier_type');
        });
    }
};
