<?php

namespace Database\Factories;

use App\Models\CategoryQuantityDiscount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoryQuantityDiscount>
 */
class CategoryQuantityDiscountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CategoryQuantityDiscount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => null,
            'min_quantity' => 10,
            'max_quantity' => null,
            'discount_type' => 'percentage',
            'discount_value' => 20,
        ];
    }
}
