<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('category_quantity_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('min_quantity');
            $table->unsignedInteger('max_quantity')->nullable();
            $table->enum('discount_type', ['percent','fixed']);
            $table->decimal('discount_value', 10, 4);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['category_id','min_quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_quantity_discounts');
    }
};
