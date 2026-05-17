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
        // 1. Variation Types (e.g., Color, Size, Thickness)
        Schema::create('variation_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 'Color', 'Size', etc.
            $table->string('presentation_type')->default('select'); // 'color_swatch', 'select', 'radio'
            $table->timestamps();
        });

        // 2. Variation Options (e.g., Red, XL, 5mm)
        Schema::create('variation_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variation_type_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // 'Red', 'XL'
            $table->string('value')->nullable(); // '#FF0000', 'xl', '5mm' (meta value)
            $table->string('color_hex')->nullable(); // '#800000'
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 3. Product Variation Types (Which variations a product uses)
        Schema::create('product_variation_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variation_type_id')->constrained()->cascadeOnDelete();
            $table->boolean('has_images')->default(false);
            $table->boolean('affects_price')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'variation_type_id']);
        });

        // 4. Product Variation Options (Specific choices for the product, and individual modifiers)
        Schema::create('product_variation_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variation_type_id')->constrained('product_variation_types')->cascadeOnDelete();
            $table->foreignId('variation_option_id')->constrained()->cascadeOnDelete();
            $table->decimal('price_modifier', 10, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['product_variation_type_id', 'variation_option_id'], 'pvo_unique');
        });

        // 5. Product SKUs (Combinations for stock tracking)
        Schema::create('product_skus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable()->unique();
            $table->unsignedInteger('quantity')->default(0);
            $table->boolean('is_available')->default(true);
            $table->decimal('override_price', 10, 2)->nullable();
            $table->timestamps();
        });

        // 6. Product SKU Options Pivot
        Schema::create('product_sku_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_sku_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variation_option_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_sku_id', 'variation_option_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_sku_options');
        Schema::dropIfExists('product_skus');
        Schema::dropIfExists('product_variation_options');
        Schema::dropIfExists('product_variation_types');
        Schema::dropIfExists('variation_options');
        Schema::dropIfExists('variation_types');
    }
};
