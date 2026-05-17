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
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('image_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('medium_path')->nullable();
            $table->string('large_path')->nullable();

            $table->text('image_url')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->text('medium_url')->nullable();
            $table->text('large_url')->nullable();

            $table->string('image_description')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('variation_option_id')->nullable()->constrained()->onDelete('set null');

            $table->integer('order_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
