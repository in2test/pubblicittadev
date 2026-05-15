<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-'.strtoupper(fake()->unique()->bothify('??####')),
            'status' => fake()->randomElement(['pending', 'paid', 'completed', 'cancelled']),
            'total_price' => fake()->randomFloat(2, 50, 500),
            'total_items' => fake()->numberBetween(1, 10),
            'user_id' => User::factory(),
        ];
    }
}
