<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:migrate-images-to-media-library')]
#[Description('Migrate existing images from Image model to Spatie Media Library')]
class MigrateImagesToMediaLibrary extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration of images to media library...');

        // Migrate product images
        $productImages = Image::whereNotNull('product_id')->get();
        $this->info("Found {$productImages->count()} product images to migrate");

        foreach ($productImages as $image) {
            $product = Product::find($image->product_id);
            if (!$product) {
                $this->warn("Product {$image->product_id} not found, skipping image {$image->id}");
                continue;
            }

            if ($image->image_path) {
                $product->addMedia(storage_path('app/public/' . $image->image_path))
                    ->usingName($image->image_description ?? 'Product Image')
                    ->toMediaCollection('images');
            } elseif ($image->image_url) {
                $product->addMediaFromUrl($image->image_url)
                    ->usingName($image->image_description ?? 'Product Image')
                    ->toMediaCollection('images');
            }

            $this->line("Migrated product image {$image->id}");
        }

        // Migrate category images
        $categoryImages = Image::whereNotNull('category_id')->get();
        $this->info("Found {$categoryImages->count()} category images to migrate");

        foreach ($categoryImages as $image) {
            $category = Category::find($image->category_id);
            if (!$category) {
                $this->warn("Category {$image->category_id} not found, skipping image {$image->id}");
                continue;
            }

            if ($image->image_path) {
                $category->addMedia(storage_path('app/public/' . $image->image_path))
                    ->usingName($image->image_description ?? 'Category Image')
                    ->toMediaCollection('images');
            } elseif ($image->image_url) {
                $category->addMediaFromUrl($image->image_url)
                    ->usingName($image->image_description ?? 'Category Image')
                    ->toMediaCollection('images');
            }

            $this->line("Migrated category image {$image->id}");
        }

        $this->info('Migration completed! You can now drop the images table if desired.');
    }
}
