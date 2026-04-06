<?php

namespace Database\Seeders;

use App\Models\Category;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'deepinart@gmail.com',
            'password' => bcrypt('adelante'),
        ]);

        // Create 4 root categories with images
        Category::factory(10)
            ->create()
            ->each(function ($category) {
                // Add media to category
                $category->addMediaFromUrl('https://picsum.photos/800/600?random=' . rand(1, 1000))
                    ->toMediaCollection('images');

                // Create 4 products for each category
                Product::factory(20)
                    ->for($category)
                    ->create()
                    ->each(function ($product) {
                        // Add multiple media to product
                        for ($i = 0; $i < rand(1, 5); $i++) {
                            $product->addMediaFromUrl('https://picsum.photos/800/600?random=' . rand(1, 1000))
                                ->toMediaCollection('images');
                        }
                    });
            });
    }
}
