<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductSku>
 */
class ProductSkuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => $this->faker->unique()->lexify('SKU-?????'),
            'quantity' => 10,
            'is_available' => true,
            'override_price' => null,
        ];
    }
}
