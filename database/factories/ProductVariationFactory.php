<?php

namespace Database\Factories;

use App\Models\Color;
use App\Models\PrintPlacement;
use App\Models\PrintSide;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariation>
 */
class ProductVariationFactory extends Factory
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
            'color_id' => Color::factory(),
            'size_id' => Size::factory(),
            'print_placement_id' => PrintPlacement::factory(),
            'print_side_id' => PrintSide::factory(),
            'sku' => $this->faker->unique()->bothify('SKU-??????'),
            'quantity' => $this->faker->numberBetween(0, 1000),
            'is_available' => $this->faker->boolean(80),
        ];
    }
}
