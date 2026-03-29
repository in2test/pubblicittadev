<?php

namespace Database\Factories;

use App\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image_path' => 'product_images/'.$this->faker->uuid().'.jpg',
            'image_url' => 'https://picsum.photos/seed/'.$this->faker->uuid().'/800/600',
            'image_description' => $this->faker->sentence(),
            'product_id' => null,
            'category_id' => null,
            'order_by' => $this->faker->numberBetween(1, 100),
        ];
    }
}
