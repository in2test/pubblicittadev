<?php

namespace Database\Factories;

use App\Models\VariationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VariationType>
 */
class VariationTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'presentation_type' => 'select',
        ];
    }
}
