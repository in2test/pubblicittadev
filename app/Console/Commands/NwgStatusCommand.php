<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductAvailabilityService;
use Illuminate\Console\Command;

class NwgStatusCommand extends Command
{
    protected $signature = 'nwg:status {sku}';
    protected $description = 'Show NWG status for a given SKU including sync state and remote_images';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $sku = (string) $this->argument('sku');
        $service = new ProductAvailabilityService();
        $product = Product::where('sku', $sku)->first();
        $this->line("Product: " . ($product?->name ?? 'N/A') . ' (' . $sku . ')');
        if ($product) {
            $this->line('Sync status: ' . ($product->sync_status ?? 'unknown'));
            $this->line('Sync progress: ' . ($product->sync_progress ?? 0));
            $this->line('Synced at: ' . ($product->synced_at ?? 'never'));
            $remote = $product->remote_images ?? [];
            $this->line('Remote images: ' . count($remote));
        }

        $data = $service->getFullProductData($sku);
        if ($data) {
            $this->line('NWG payload fetched: OK');
            $this->line('Top-level pics: ' . count($data['pictures'] ?? []));
            $this->line('Variations: ' . count($data['variations'] ?? []));
        } else {
            $this->line('NWG payload: No data');
        }
        return 0;
    }
}
