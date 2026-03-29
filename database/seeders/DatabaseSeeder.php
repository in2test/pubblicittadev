<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'deepinart@gmail.com',
            'password' => bcrypt('adelante'),
        ]);

        $categories = [
            [
                'name' => 'Workwear',
                'slug' => 'workwear',
                'description' => 'Divise tecniche personalizzate con certificazione antinfortunistica.',
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDgP8uLp7eB6XfTynsWgwlqFLzm161lsuaW8Iw_V1n46SkAlUof34qX1KovcVuPGb-3BJ8fi8HBrLCWVtioFS3zZpMyXsTtdgSXSpWoZ9zQj7WYotNAitEyoZVTGu1j_PUJpZ4eIj01OUmzQTXcnjplka9159ZOa_TiknuG0D5xi0d-9Sgqns5kmQ0YJ_iwTuYkpz4rW2uaVC8E4iOiUrdq59l9jHfNrBo5ewhlnIPhdEnptXwxppgeEDcT6D8Ee7Hu5ewSO47ddQs',
            ],
            [
                'name' => 'Stampa Digitale',
                'slug' => 'stampa-digitale',
                'description' => 'Comunicazione visiva per cantieri e spazi industriali.',
                'image_url' => 'https://images.nwgmedia.com/standard/333970/359418_354428_IMss24_WaunaCapSeabeckPolo.jpg',
            ],
            [
                'name' => 'Piccolo Formato',
                'slug' => 'piccolo-formato',
                'description' => 'Cataloghi Tecnici, Schede Prodotto, Packaging Industriale.',
                'image_url' => 'https://images.nwgmedia.com/standard/156339/022042_IM_CLIQUE_2019.jpg',
            ],
            [
                'name' => 'Branding Strategy',
                'slug' => 'branding-strategy',
                'description' => 'Dall\'analisi del logo alla sua applicazione su ogni supporto.',
                'image_url' => 'https://images.nwgmedia.com/standard/715867/028230_BasicPolo_ss26_v9%20copy.jpg',
            ],
        ];

        foreach ($categories as $catData) {
            $imageUrl = $catData['image_url'];
            unset($catData['image_url']);

            $category = \App\Models\Category::create($catData);
            $category->image()->create([
                'image_url' => $imageUrl,
            ]);

            // Create some products for each category
            $products = [
                ['name' => $catData['name'] . ' Pro-X', 'price' => rand(20, 150)],
                ['name' => $catData['name'] . ' Corporate', 'price' => rand(15, 80)],
            ];

            foreach ($products as $prodData) {
                $product = \App\Models\Product::create([
                    'name' => $prodData['name'],
                    'slug' => \Illuminate\Support\Str::slug($prodData['name']),
                    'description' => 'Descrizione tecnica di alta qualità per ' . $prodData['name'],
                    'price' => $prodData['price'],
                    'category_id' => $category->id,
                    'is_featured' => (bool)rand(0, 1),
                ]);

                $product->images()->create([
                    'image_url' => $imageUrl,
                ]);
            }
        }
    }
}
