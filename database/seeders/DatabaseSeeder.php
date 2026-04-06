<?php

namespace Database\Seeders;

use App\Models\Category;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Image;
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
            ->has(Image::factory())
            ->create()
            ->each(function ($category) {
                // Create 4 products for each category
                Product::factory(20)
                    ->for($category)
                    ->has(Image::factory(5))
                    ->create();
            });
    }
}
