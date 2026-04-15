<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate tables for a clean state
        Schema::disableForeignKeyConstraints();
        Product::truncate();
        Category::truncate();
        Schema::enableForeignKeyConstraints();

        $url = 'https://connect.gateway.nwg.se/api/YdpsNwk2BEWc0DhfsLCrSg';
        $response = Http::get($url);

        if (!$response->successful()) {
            return;
        }

        $productsData = $response->json();
        
        // Take only the first 20 products as requested
        $productsToSeed = array_slice($productsData, 0, 20);

        foreach ($productsToSeed as $data) {
            $categoryNameIt = $data['productCategory']['it'] ?? 'Uncategorized';
            $category = $this->getOrCreateCategoryHierarchy($categoryNameIt);

            $product = Product::create([
                'name' => $data['productName']['it'],
                'slug' => Str::slug($data['productName']['it'].'-'.$data['productNumber']),
                'description' => $data['description']['it'] ?? '',
                'sku' => $data['productNumber'],
                'price' => $data['retailPrice']['value'] ?? 0,
                'category_id' => $category->id,
                'is_featured' => false,
            ]);

            // Attach first 2 images
            $images = array_slice($data['images'] ?? [], 0, 2);
            foreach ($images as $imageData) {
                if (isset($imageData['preview'])) {
                    try {
                        $product->addMediaFromUrl($imageData['preview'])
                            ->toMediaCollection('images');
                    } catch (\Exception $e) {
                        // Log or handle failed download
                    }
                }
            }
        }
    }

    /**
     * Parse semicolon-separated category string and create hierarchy
     */
    private function getOrCreateCategoryHierarchy(string $categoryString): Category
    {
        $categoryNames = array_map('trim', explode(';', $categoryString));
        $parentId = null;
        $category = null;

        foreach ($categoryNames as $name) {
            $slug = Str::slug($name);
            $category = Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'parent_id' => $parentId,
                ]
            );
            $parentId = $category->id;
        }

        return $category;
    }
}
