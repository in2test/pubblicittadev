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
        Schema::table('images', function (Blueprint $table) {
            $table->text('thumbnail_url')->nullable()->after('image_url');
            $table->text('medium_url')->nullable()->after('thumbnail_url');
            $table->text('large_url')->nullable()->after('medium_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn(['thumbnail_url', 'medium_url', 'large_url']);
        });
    }
};
