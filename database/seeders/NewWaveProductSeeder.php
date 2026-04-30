<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\\Facades\\Log;
use Exception;

class NewWaveProductSeeder extends Seeder
{
    public function run(): void
    {
        // Seed a single NewWave product with real NWG URLs to exercise the caching flow
        $product = Product::create([
            'sku' => 'NWG-TEST-001',
            'name' => 'NewWave Test Product',
            'description' => 'Seeded for on-demand image caching flow',
            'price' => 9.99,
            'type' => Product::TYPE_NEWWAVE,
            'sync_progress' => 0,
            'is_active' => true,
        ]);

        // Attach a handful of real NWG image URLs
        // Seed remote_images for CDN-first flow (do not download images on import)
        $remoteImages = [
            [
                'id' => 'top_710793',
                'url' => 'https://images.nwgmedia.com/standard/710793/029029-956_Basic-T-Loose-Fit_Front.jpg',
                'thumb' => 'https://images.nwgmedia.com/thumbnail/710793/029029-956_Basic-T-Loose-Fit_Front.jpg',
                'medium' => 'https://images.nwgmedia.com/largethumbnail/710793/029029-956_Basic-T-Loose-Fit_Front.jpg',
                'color_ids' => [],
            ],
        ];
        $product->remote_images = $remoteImages;
        $product->save();
    }
}
