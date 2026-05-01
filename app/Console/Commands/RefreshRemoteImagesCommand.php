<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductAvailabilityService;
use Illuminate\Console\Command;

class RefreshRemoteImagesCommand extends Command
{
    protected $signature = 'products:refresh-remote-images {product_id}';
    protected $description = 'Refresh remote_images for a product from the NWG API payload';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $productId = (int) $this->argument('product_id');
        $product = Product::find($productId);
        if (!$product) {
            $this->error("Product with id {$productId} not found.");
            return 1;
        }

        $service = new ProductAvailabilityService();
        $data = $service->getFullProductData($product->sku);
        if (!$data) {
            $this->error("No data returned for SKU {$product->sku} from API.");
            return 1;
        }

        $remoteImages = $service->mapFullProductPayloadToRemoteImages($data);
        if (!empty($remoteImages)) {
            $product->update(['remote_images' => $remoteImages]);
            $this->info("Updated remote_images for Product ID {$product->id}.");
        } else {
            $this->info("No remote_images derived from API payload for Product ID {$product->id}.");
        }

        return 0;
    }
}
