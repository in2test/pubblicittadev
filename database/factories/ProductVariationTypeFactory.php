<?php

namespace Database\Factories;

use App\Models\ProductVariationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariationType>
 */
class ProductVariationTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'pivot' => [
                'is_modifier' => false,
                'modifier_type' => null,
                'modifier_value' => null,
            ],
        ];
    }

    /**
     * Indicate that the variation type is a modifier.
     */
    public function modifier(): static
    {
        return $this->state(fn (array $attributes) => [
            'pivot' => [
                'is_modifier' => true,
                'modifier_type' => fake()->randomElement(['percentage', 'flat']),
                'modifier_value' => fake()->numberBetween(1, 50),
            ],
        ]);
    }
}
