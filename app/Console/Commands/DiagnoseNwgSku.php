<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ProductAvailabilityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DiagnoseNwgSku extends Command
{
    protected $signature = 'nwg:diagnose {sku}';
    protected $description = 'Fetch NWG API data for a given SKU and dump the structure for debugging';

    public function handle(): int
    {
        $sku = (string) $this->argument('sku');
        $service = new ProductAvailabilityService();
        $data = $service->getFullProductData($sku);
        if (!$data) {
            $this->error('No data returned');
            return 1;
        }
        $this->info(print_r($data, true));
        return 0;
    }
}
