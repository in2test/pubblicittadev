<?php

namespace Database\Factories;

use App\Enums\ModifierType;
use App\Models\ProductVariationOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariationOption>
 */
class ProductVariationOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'variation_option_id' => fake()->numberBetween(1, 100),
            'product_variation_type_id' => fake()->numberBetween(1, 100),
            'modifier_type' => ModifierType::Percentage,
            'price_modifier' => null, // Use global default
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }

    /**
     * Indicate that the option has a specific modifier type.
     */
    public function percentage(): static
    {
        return $this->state(fn (array $attributes) => [
            'modifier_type' => ModifierType::Percentage,
        ]);
    }

    /**
     * Indicate that the option has a flat modifier.
     */
    public function flat(): static
    {
        return $this->state(fn (array $attributes) => [
            'modifier_type' => ModifierType::Flat,
        ]);
    }
}
