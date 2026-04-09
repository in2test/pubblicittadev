<?php

namespace Database\Seeders;

use App\Models\Category;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Color;
use App\Models\PrintPlacement;
use App\Models\PrintSide;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Size;
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
        Category::factory(4)
            ->create()
            ->each(function ($category) {
                // Add media to category
                $category->addMediaFromUrl('https://picsum.photos/800/600?random='.rand(1, 1000))
                    ->toMediaCollection('images');

                // Create 5 products for each category
                Product::factory(5)
                    ->for($category)
                    ->create()
                    ->each(function ($product) {
                        // Add multiple media to product
                        for ($i = 0; $i < rand(1, 5); $i++) {
                            $product->addMediaFromUrl('https://picsum.photos/800/600?random='.rand(1, 1000))
                                ->toMediaCollection('images');
                        }
                    });
            });

        // Seed Print Placements
        PrintPlacement::create(['name' => 'Fronte', 'description' => 'Stampa sul fronte 23x30 cm', 'sort_order' => 1]);
        PrintPlacement::create(['name' => 'Dietro', 'description' => 'Stampa sul dietro 23x30 cm', 'sort_order' => 2]);
        PrintPlacement::create(['name' => 'Manica Sinistra', 'description' => 'Stampa sulla manica sinistra 9x9 cm', 'sort_order' => 3]);
        PrintPlacement::create(['name' => 'Manica Destra', 'description' => 'Stampa sulla manica destra 9x9 cm', 'sort_order' => 4]);
        PrintPlacement::create(['name' => 'Lato Cuore', 'description' => 'Stampa sul lato cuore 9x9 cm', 'sort_order' => 5]);
        PrintPlacement::create(['name' => 'Tasca', 'description' => 'Stampa sulla tasca 9x9 cm', 'sort_order' => 6]);
        PrintPlacement::create(['name' => 'Gamba sinistra', 'description' => 'Stampa sulla gamba sinistra 9x9 cm', 'sort_order' => 7]);
        PrintPlacement::create(['name' => 'Gamba destra', 'description' => 'Stampa sulla gamba destra 9x9 cm', 'sort_order' => 8]);

        // Seed Print Sides
        PrintSide::create(['name' => 'Stampa sul fronte', 'description' => 'Stampa solo sul fronte', 'sort_order' => 1]);
        PrintSide::create(['name' => 'Fronte e retro uguali', 'description' => 'Stampa fronte e retro uguali', 'sort_order' => 2]);
        PrintSide::create(['name' => 'Fronte e retro differenti', 'description' => 'Stampa fronte e retro differenti', 'sort_order' => 3]);

        // Seed Sizes
        Size::create(['size_name' => 'Extra Small', 'size' => 'XS', 'sort_order' => 1]);
        Size::create(['size_name' => 'Small', 'size' => 'S', 'sort_order' => 2]);
        Size::create(['size_name' => 'Medium', 'size' => 'M', 'sort_order' => 3]);
        Size::create(['size_name' => 'Large', 'size' => 'L', 'sort_order' => 4]);
        Size::create(['size_name' => 'Extra Large', 'size' => 'XL', 'sort_order' => 5]);
        Size::create(['size_name' => 'XXL', 'size' => 'XXL', 'sort_order' => 6]);
        Size::create(['size_name' => '3XL', 'size' => '3XL', 'sort_order' => 7]);
        Size::create(['size_name' => '4XL', 'size' => '4XL', 'sort_order' => 8]);
        Size::create(['size_name' => '5XL', 'size' => '5XL', 'sort_order' => 9]);
        Size::create(['size_name' => '6XL', 'size' => '6XL', 'sort_order' => 10]);
        Size::create(['size_name' => '6-8 anni', 'size' => '110/120', 'sort_order' => 11]);
        Size::create(['size_name' => '9-11 anni', 'size' => '130/140', 'sort_order' => 12]);
        Size::create(['size_name' => '12-14 anni', 'size' => '150/160', 'sort_order' => 13]);
        // Seed Colors
        Color::create(['color_name' => 'Bianco', 'color_hex' => '#ffffff', 'color_code' => '00', 'sort_order' => 1]);
        Color::create(['color_name' => 'Bianco Avorio', 'color_hex' => '#fffeef', 'color_code' => '01', 'sort_order' => 2]);
        Color::create(['color_name' => 'Khaki', 'color_hex' => '#c3b091', 'color_code' => '04', 'sort_order' => 3]);
        Color::create(['color_name' => 'Bianco Perla', 'color_hex' => '#eae6ca', 'color_code' => '07', 'sort_order' => 4]);
        Color::create(['color_name' => 'Giallo Limone', 'color_hex' => '#c7b446', 'color_code' => '10', 'sort_order' => 5]);
        Color::create(['color_name' => 'Giallo HV', 'color_hex' => '#ffff00', 'color_code' => '11', 'sort_order' => 6]);
        Color::create(['color_name' => 'Arancio HV', 'color_hex' => '#ff2301', 'color_code' => '170', 'sort_order' => 7]);
        Color::create(['color_name' => 'Arancione', 'color_hex' => '#ffa500', 'color_code' => '175', 'sort_order' => 8]);
        Color::create(['color_name' => 'Arancio', 'color_hex' => '#ff9900', 'color_code' => '18', 'sort_order' => 9]);
        Color::create(['color_name' => 'Arancio Bruciato', 'color_hex' => '#ff7514', 'color_code' => '19', 'sort_order' => 10]);
        Color::create(['color_name' => 'Rosa Antico', 'color_hex' => '#d36e70', 'color_code' => '203', 'sort_order' => 11]);
        Color::create(['color_name' => 'Rosa Confetto', 'color_hex' => '#fadadd', 'color_code' => '215', 'sort_order' => 12]);
        Color::create(['color_name' => 'Rosa Active', 'color_hex' => '#fc0fc0', 'color_code' => '240', 'sort_order' => 13]);
        Color::create(['color_name' => 'Rosa Brillante', 'color_hex' => '#ff007f', 'color_code' => '250', 'sort_order' => 14]);
        Color::create(['color_name' => 'Lampone', 'color_hex' => '#e30b5c', 'color_code' => '300', 'sort_order' => 15]);
        Color::create(['color_name' => 'Rosso', 'color_hex' => '#ff0000', 'color_code' => '35', 'sort_order' => 16]);
        Color::create(['color_name' => 'Bordeaux', 'color_hex' => '#800000', 'color_code' => '38', 'sort_order' => 17]);
        Color::create(['color_name' => 'Viola', 'color_hex' => '#8f00ff', 'color_code' => '44', 'sort_order' => 18]);
        Color::create(['color_name' => 'Turchese', 'color_hex' => '#30d5c8', 'color_code' => '54', 'sort_order' => 19]);
        Color::create(['color_name' => 'Royal', 'color_hex' => '#4169e1', 'color_code' => '55', 'sort_order' => 20]);
        Color::create(['color_name' => 'Navy Mélange', 'color_hex' => '#000080', 'color_code' => '554', 'sort_order' => 21]);
        Color::create(['color_name' => 'Cobalto', 'color_hex' => '#0047ab', 'color_code' => '56', 'sort_order' => 22]);
        Color::create(['color_name' => 'Blue Mélange', 'color_hex' => '#5f9ea0', 'color_code' => '565', 'sort_order' => 23]);
        Color::create(['color_name' => 'Azzurro', 'color_hex' => '#007fff', 'color_code' => '57', 'sort_order' => 24]);
        Color::create(['color_name' => 'Azzurro Pastello', 'color_hex' => '#afeeee', 'color_code' => '570', 'sort_order' => 25]);
        Color::create(['color_name' => 'Blu Nebbia', 'color_hex' => '#778899', 'color_code' => '575', 'sort_order' => 26]);
        Color::create(['color_name' => 'Blu Navy', 'color_hex' => '#000080', 'color_code' => '58', 'sort_order' => 27]);
        Color::create(['color_name' => 'Blu Scuro', 'color_hex' => '#00008b', 'color_code' => '580', 'sort_order' => 28]);
        Color::create(['color_name' => 'Denim', 'color_hex' => '#1560bd', 'color_code' => '581', 'sort_order' => 29]);
        Color::create(['color_name' => 'Blu Acciaio', 'color_hex' => '#4682b4', 'color_code' => '595', 'sort_order' => 30]);
        Color::create(['color_name' => 'Verde Lime', 'color_hex' => '#ccff00', 'color_code' => '600', 'sort_order' => 31]);
        Color::create(['color_name' => 'Verde Active', 'color_hex' => '#00ff00', 'color_code' => '602', 'sort_order' => 32]);
        Color::create(['color_name' => 'Verde Acido', 'color_hex' => '#7fff00', 'color_code' => '605', 'sort_order' => 33]);
        Color::create(['color_name' => 'Verde Salvia', 'color_hex' => '#9dc183', 'color_code' => '615', 'sort_order' => 34]);
        Color::create(['color_name' => 'Verde Bandiera', 'color_hex' => '#228b22', 'color_code' => '62', 'sort_order' => 35]);
        Color::create(['color_name' => 'Verde Foresta', 'color_hex' => '#228b22', 'color_code' => '66', 'sort_order' => 36]);
        Color::create(['color_name' => 'Verde Chiaro', 'color_hex' => '#66ff00', 'color_code' => '67', 'sort_order' => 37]);
        Color::create(['color_name' => 'Verde Bottiglia', 'color_hex' => '#343b29', 'color_code' => '68', 'sort_order' => 38]);
        Color::create(['color_name' => 'Verde Militare', 'color_hex' => '#556832', 'color_code' => '71', 'sort_order' => 39]);
        Color::create(['color_name' => 'Verde Bamboo', 'color_hex' => '#40826d', 'color_code' => '75', 'sort_order' => 40]);
        Color::create(['color_name' => 'Beige', 'color_hex' => '#f5f5dc', 'color_code' => '815', 'sort_order' => 41]);
        Color::create(['color_name' => 'Sabbia', 'color_hex' => '#f4a460', 'color_code' => '82', 'sort_order' => 42]);
        Color::create(['color_name' => 'Caffè', 'color_hex' => '#d2691e', 'color_code' => '820', 'sort_order' => 43]);
        Color::create(['color_name' => 'Marrone Moka', 'color_hex' => '#8a5a3a', 'color_code' => '825', 'sort_order' => 44]);
        Color::create(['color_name' => 'Grigio', 'color_hex' => '#808080', 'color_code' => '90', 'sort_order' => 45]);
        Color::create(['color_name' => 'Grigio Pietra', 'color_hex' => '#8b8c7a', 'color_code' => '91', 'sort_order' => 46]);
        Color::create(['color_name' => 'Grigio Cenere', 'color_hex' => '#e4e5e0', 'color_code' => '92', 'sort_order' => 47]);
        Color::create(['color_name' => 'Nature Mèlange', 'color_hex' => '#b5b8b1', 'color_code' => '925', 'sort_order' => 48]);
        Color::create(['color_name' => 'Grigio Argento', 'color_hex' => '#c0c0c0', 'color_code' => '94', 'sort_order' => 49]);
        Color::create(['color_name' => 'Grigio Fumo', 'color_hex' => '#e5e5e5', 'color_code' => '945', 'sort_order' => 50]);
        Color::create(['color_name' => 'Rifrangente', 'color_hex' => '#f4f4f4', 'color_code' => '947', 'sort_order' => 51]);
        Color::create(['color_name' => 'Rifrangente Chiaro', 'color_hex' => '#f6f6f6', 'color_code' => '949', 'sort_order' => 52]);
        Color::create(['color_name' => 'Grigio Mèlange', 'color_hex' => '#b2b2b2', 'color_code' => '95', 'sort_order' => 53]);
        Color::create(['color_name' => 'Antracite Mèlange', 'color_hex' => '#293133', 'color_code' => '955', 'sort_order' => 54]);
        Color::create(['color_name' => 'Grigio Metallo', 'color_hex' => '#a8a9ad', 'color_code' => '956', 'sort_order' => 55]);
        Color::create(['color_name' => 'Canna di Fucile', 'color_hex' => '#2f4f4f', 'color_code' => '96', 'sort_order' => 56]);
        Color::create(['color_name' => 'Nero', 'color_hex' => '#000000', 'color_code' => '99', 'sort_order' => 57]);

        // Seed Product Variations
        // Create variations for each product (limited to avoid excessive data)
        $variationCounter = 0;
        Product::all()->each(function ($product) use (&$variationCounter) {
            $colors = Color::inRandomOrder()->take(2)->pluck('id');
            $sizes = Size::inRandomOrder()->take(2)->pluck('id');
            $placements = PrintPlacement::all()->pluck('id');
            $sides = PrintSide::all()->pluck('id');

            foreach ($colors as $colorId) {
                foreach ($sizes as $sizeId) {
                    $variationCounter++;
                    ProductVariation::create([
                        'product_id' => $product->id,
                        'color_id' => $colorId,
                        'size_id' => $sizeId,
                        'print_placement_id' => $placements->random(),
                        'print_side_id' => $sides->random(),
                        'sku' => $product->sku.'-VAR-'.str_pad($variationCounter, 4, '0', STR_PAD_LEFT),
                        'quantity' => rand(10, 500),
                        'is_available' => true,
                    ]);
                }
            }
        });

    }
}
