<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Size;
use Illuminate\Database\Seeder;
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
        // Increase memory limit to handle large JSON response
        ini_set('memory_limit', '512M');

        // Truncate tables for a clean state
        Schema::disableForeignKeyConstraints();
        Product::truncate();
        Category::truncate();
        ProductVariation::truncate();
        Schema::enableForeignKeyConstraints();

        $url = 'https://connect.gateway.nwg.se/api/jPEELCU7kORztJHwtz6Iw';
        $response = Http::withoutVerifying()->get($url);

        if (! $response->successful()) {
            return;
        }

        $productsData = $response->json();

        // Take only the first 20 products as requested
        $productsToSeed = array_slice($productsData, 0, 50);

        foreach ($productsToSeed as $data) {
            $categoryNameIt = $data['productCategory']['it'] ?? 'Uncategorized';
            $category = $this->getOrCreateCategoryHierarchy($categoryNameIt);

            // Find minimum price among variations
            $minPrice = collect($data['variations'] ?? [])
                ->flatMap(fn ($v) => $v['skus'] ?? [])
                ->pluck('retailPrice.value')
                ->filter()
                ->min() ?? ($data['retailPrice']['value'] ?? 0);

            $product = Product::create([
                'name' => $data['productName']['it'],
                'slug' => Str::slug($data['productName']['it'].'-'.$data['productNumber']),
                'description' => $data['description']['it'] ?? '',
                'sku' => $data['productNumber'],
                'price' => $minPrice,
                'category_id' => $category->id,
                'is_featured' => false,
            ]);

            $imageToColors = [];

            // Add main images with no specific color defaults
            foreach (array_slice($data['images'] ?? [], 0, 30) as $img) {
                if (isset($img['preview'])) {
                    $imageToColors[$img['preview']] = [];
                }
            }

            // Seed Variations & Collect Colors mapping to images
            if (isset($data['variations']) && is_array($data['variations'])) {
                foreach ($data['variations'] as $variationData) {
                    $colorCode = $variationData['colorCode'] ?? null;
                    if (! $colorCode) {
                        continue;
                    }

                    // Find matching Color based on color_code
                    $color = Color::where('color_code', $colorCode)->first();
                    if (! $color) {
                        continue;
                    }

                    // Map images to this color
                    if (isset($variationData['images']) && is_array($variationData['images'])) {
                        foreach ($variationData['images'] as $img) {
                            if (isset($img['preview'])) {
                                if (! isset($imageToColors[$img['preview']])) {
                                    $imageToColors[$img['preview']] = [];
                                }
                                if (! in_array($color->id, $imageToColors[$img['preview']])) {
                                    $imageToColors[$img['preview']][] = $color->id;
                                }
                            }
                        }
                    }

                    if (isset($variationData['skus']) && is_array($variationData['skus'])) {
                        foreach ($variationData['skus'] as $skuData) {
                            $sizeName = $skuData['name'] ?? null;
                            if (! $sizeName) {
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
                                'is_available' => ! empty($skuData['active']),
                            ]);
                        }
                    }
                }
            }

            // Download up to 30 images with color tracking
            $imageCount = 0;
            foreach ($imageToColors as $url => $colorIds) {
                if ($imageCount >= 30) {
                    break;
                }
                try {
                    $mediaAdder = $product->addMediaFromUrl($url);
                    if (! empty($colorIds)) {
                        $mediaAdder->withCustomProperties(['color_ids' => $colorIds]);
                    }
                    $mediaAdder->toMediaCollection('images');
                    $imageCount++;
                } catch (\Exception $e) {
                    // Log or handle failed download
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
