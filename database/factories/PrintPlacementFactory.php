<?php

namespace Database\Factories;

use App\Models\PrintPlacement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrintPlacement>
 */
class PrintPlacementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'description' => $this->faker->optional()->sentence(),
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
