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
            $table->foreignId('print_side_id')->nullable()->constrained('print_sides')->nullOnDelete();
        });

        Schema::table('print_placements', function (Blueprint $table) {
            $table->string('template_path')->nullable();
        });

        Schema::table('print_sides', function (Blueprint $table) {
            $table->string('template_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_tiers', function (Blueprint $table) {
            $table->dropForeign(['print_side_id']);
            $table->dropColumn('print_side_id');
        });

        Schema::table('print_placements', function (Blueprint $table) {
            $table->dropColumn('template_path');
        });

        Schema::table('print_sides', function (Blueprint $table) {
            $table->dropColumn('template_path');
        });
    }
};
