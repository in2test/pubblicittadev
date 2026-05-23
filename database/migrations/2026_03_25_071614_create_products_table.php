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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('standard');

            $table->string('pricing_model')->default('fixed');
            $table->decimal('min_area', 8, 4)->nullable();
            $table->decimal('max_width', 8, 2)->nullable();
            $table->decimal('max_height', 8, 2)->nullable();

            $table->decimal('sheet_width', 8, 2)->nullable();
            $table->decimal('sheet_height', 8, 2)->nullable();
            $table->boolean('allows_custom_size')->default(false);
            $table->decimal('min_custom_width', 8, 2)->nullable();
            $table->decimal('max_custom_width', 8, 2)->nullable();
            $table->decimal('min_custom_height', 8, 2)->nullable();
            $table->decimal('max_custom_height', 8, 2)->nullable();

            $table->string('sync_status')->default('pending');
            $table->json('remote_images')->nullable();
            $table->unsignedTinyInteger('sync_progress')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->boolean('is_active')->default(false);

            $table->string('sku')->unique()->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('override_description')->default(false);

            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('offer_price', 10, 2)->nullable();
            $table->boolean('override_price')->default(false);

            $table->boolean('is_featured')->default(false);

            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
