<?php

namespace Database\Factories;

use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VariationOption>
 */
class VariationOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'variation_type_id' => VariationType::factory(),
            'name' => $this->faker->word(),
            'value' => null,
            'sort_order' => 0,
        ];
    }
}
