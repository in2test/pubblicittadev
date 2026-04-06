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
            $table->string('thumbnail_path')->nullable()->after('image_path');
            $table->string('medium_path')->nullable()->after('thumbnail_path');
            $table->string('large_path')->nullable()->after('medium_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn(['thumbnail_path', 'medium_path', 'large_path']);
        });
    }
};
