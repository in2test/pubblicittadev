<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

#[Signature('app:migrate-images-to-media-library')]
#[Description('Migrate existing images from Image model to Spatie Media Library')]
class MigrateImagesToMediaLibrary extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting migration of images to media library...');

        // Migrate product images
        $productImageCount = Image::query()->whereNotNull('product_id')->count();
        $this->info("Found {$productImageCount} product images to migrate");
        Image::query()
            ->with('product')
            ->whereNotNull('product_id')
            ->chunkById(500, function (Collection $productImages): void {
                foreach ($productImages as $image) {
                    /** @var Product|null $product */
                    $product = $image->product;
                    if (! $product) {
                        $this->warn("Product {$image->product_id} not found, skipping image {$image->id}");

                        continue;
                    }

                    if ($image->image_path) {
                        $product->addMedia(storage_path('app/public/'.$image->image_path))
                            ->usingName($image->image_description ?? 'Product Image')
                            ->toMediaCollection('images');
                    } elseif ($image->image_url) {
                        $product->addMediaFromUrl($image->image_url)
                            ->usingName($image->image_description ?? 'Product Image')
                            ->toMediaCollection('images');
                    }

                    $this->line("Migrated product image {$image->id}");
                }
            });

        // Migrate category images
        $categoryImageCount = Image::query()->whereNotNull('category_id')->count();
        $this->info("Found {$categoryImageCount} category images to migrate");
        Image::query()
            ->with('category')
            ->whereNotNull('category_id')
            ->chunkById(500, function (Collection $categoryImages): void {
                foreach ($categoryImages as $image) {
                    /** @var Category|null $category */
                    $category = $image->category;
                    if (! $category) {
                        $this->warn("Category {$image->category_id} not found, skipping image {$image->id}");

                        continue;
                    }

                    if ($image->image_path) {
                        $category->addMedia(storage_path('app/public/'.$image->image_path))
                            ->usingName($image->image_description ?? 'Category Image')
                            ->toMediaCollection('images');
                    } elseif ($image->image_url) {
                        $category->addMediaFromUrl($image->image_url)
                            ->usingName($image->image_description ?? 'Category Image')
                            ->toMediaCollection('images');
                    }

                    $this->line("Migrated category image {$image->id}");
                }
            });

        $this->info('Migration completed! You can now drop the images table if desired.');
    }
}
