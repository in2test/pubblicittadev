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
