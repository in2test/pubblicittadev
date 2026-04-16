<?php

namespace Database\Seeders;

use App\Models\ProductVariation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Color;
use App\Models\Size;
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
        ProductVariation::truncate();
        Schema::enableForeignKeyConstraints();

        $url = 'https://connect.gateway.nwg.se/api/jPEELCU7kORztJHwtz6Iw';
        $response = Http::get($url);

        if (!$response->successful()) {
            return;
        }

        $productsData = $response->json();
        
        // Take only the first 20 products as requested
        $productsToSeed = array_slice($productsData, 0, 50);

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

            // Attach first 30 images to product
            $images = array_slice($data['images'] ?? [], 0, 30);
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

            // Seed Variations
            if (isset($data['variations']) && is_array($data['variations'])) {
                foreach ($data['variations'] as $variationData) {
                    $colorCode = $variationData['colorCode'] ?? null;
                    if (!$colorCode) {
                        continue;
                    }

                    // Find matching Color based on color_code
                    $color = Color::where('color_code', $colorCode)->first();

                    if ($color && isset($variationData['skus']) && is_array($variationData['skus'])) {
                        foreach ($variationData['skus'] as $skuData) {
                            $sizeName = $skuData['name'] ?? null;
                            if (!$sizeName) {
                                continue;
                            }

                            // Find or create Size based on 'size' column (e.g. 'S', 'M', 'L')
                            $size = Size::firstOrCreate(
                                ['size' => $sizeName],
                                ['size_name' => $sizeName]
                            );

                            ProductVariation::create([
                                'product_id' => $product->id,
                                'color_id' => $color->id,
                                'size_id' => $size->id,
                                'sku' => $skuData['sku'],
                                'quantity' => rand(10, 100), // Default random quantity
                                'is_available' => !empty($skuData['active']),
                            ]);
                        }
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
