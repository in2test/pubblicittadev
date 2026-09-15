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
            $table->boolean('expose_in_url')->default(false)->after('allow_multiple');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('variation_types', function (Blueprint $table) {
            $table->dropColumn('expose_in_url');
        });
    }
};
