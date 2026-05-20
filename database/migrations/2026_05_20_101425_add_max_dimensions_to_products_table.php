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
            // Maximum printable dimensions in cm. Null means unlimited on that axis.
            // Used to warn customers when a job must be split across multiple sheets.
            $table->decimal('max_width', 8, 2)->nullable()->after('min_area');
            $table->decimal('max_height', 8, 2)->nullable()->after('max_width');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['max_width', 'max_height']);
        });
    }
};
