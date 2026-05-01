<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductAvailabilityService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('products:sync-all-remote-images')] 
#[Description('Synchronize remote_images for all NWG/newwave products from the API payload')] 
class SyncAllRemoteImagesCommand extends Command
{
    // signature/description are provided via PHP 8 attributes above the class

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $service = new ProductAvailabilityService();
        $count = 0;
        $errors = 0;
        // Load all products that likely come from NWG (newwave type or with sku)
        $products = Product::whereNotNull('sku')->get();
        foreach ($products as $product) {
            $data = $service->getFullProductData($product->sku);
            if (!$data) {
                $errors++;
                $this->line("[WARN] No API data for SKU {$product->sku} (Product ID {$product->id})");
                continue;
            }
            $remoteImages = $service->mapFullProductPayloadToRemoteImages($data);
            if (!empty($remoteImages)) {
                $product->update(['remote_images' => $remoteImages]);
                $count++;
            } else {
                $this->line("[INFO] No remote_images derived for SKU {$product->sku} (Product ID {$product->id})");
            }
        }
        $this->info("Sync complete. Updated: {$count}, Errors: {$errors}");
        return 0;
    }
}
