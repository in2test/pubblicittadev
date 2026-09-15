<?php

namespace Database\Factories;

use App\Enums\ProductClass;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->paragraph(),
            'sku' => fake()->unique()->bothify('PRD-######'),
            'price' => fake()->randomFloat(2, 10, 1000),
            'product_class' => ProductClass::ItemBased,
            'category_id' => Category::factory(),
            'is_featured' => fake()->boolean(),
        ];
    }
}
