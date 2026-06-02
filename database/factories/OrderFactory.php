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
            'payment_status' => fake()->randomElement(['pending', 'paid', 'cancelled']),
            'work_status' => fake()->randomElement(['pending', 'processing', 'ready', 'shipped', 'completed']),
            'items_total' => fake()->randomFloat(2, 50, 500),
            'shipping_cost' => fake()->randomFloat(2, 0, 20),
            'shipping_method' => fake()->randomElement(['delivery', 'pickup']),
            'total_price' => function (array $attributes) {
                return $attributes['items_total'] + $attributes['shipping_cost'];
            },
            'total_items' => fake()->numberBetween(1, 10),
            'user_id' => User::factory(),
        ];
    }
}
