<?php

namespace Database\Factories;

use App\Models\Color;
use Illuminate\Database\Eloquent\Factories\Factory;

class ColorFactory extends Factory
{
    protected $model = Color::class;

    public function definition(): array
    {
        return [
            'color_name' => fake()->unique()->colorName(),
            'color_hex' => fake()->hexColor(),
            'color_code' => fake()->unique()->bothify('???###'),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
