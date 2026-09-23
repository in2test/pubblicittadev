<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductSku;
use Exception;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:mark-outlet-skus')]
#[Description('Mark SKUs as outlet and set their override price for a specific product')]
class MarkOutletSkus extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        return $this->markSkusFromArguments();
    }

    private function markSkusFromArguments(): int
    {
        $productId = $this->input->getParameterOption(['--product', '--id']);
        $skus = $this->input->getParameterOption(['--sku', '--skus']);
        $outletColorIds = $this->input->getParameterOption(['--color', '--colors']);
        $price = $this->input->getParameterOption(['--price']);

        if (! $productId) {
            $this->error('Required option: --product= or --id=<id>');

            return Command::FAILURE;
        }

        if (! $skus) {
            $this->error('Required option: --sku= with SKU numbers (space separated, e.g. 021034-BLU-ONE)');

            return Command::FAILURE;
        }

        if ($outletColorIds === null) {
            $this->error('Required option: --color=<color_option_id> to mark these SKUs as outlet');

            return Command::FAILURE;
        }

        try {
            DB::beginTransaction();

            $product = Product::find($productId);
            if (! $product) {
                $this->error("Product not found with id: {$productId}");

                return Command::FAILURE;
            }

            $skuIdsToUpdate = [];
            foreach ($skus as $skuStr) {
                $existing = ProductSku::where('product_id', $productId)->where('sku', $skuStr)->first();
                if ($existing) {
                    $skuIdsToUpdate[] = $existing->id;
                } else {
                    $this->info("SKU not found: {$skuStr}");
                }
            }

            if ($skuIdsToUpdate === []) {
                $this->error('No SKUs found to update.');

                return Command::FAILURE;
            }

            // Mark as outlet and set price
            ProductSku::whereIn('id', $skuIdsToUpdate)->update([
                'is_outlet' => true,
                'override_price' => isset($price) ? (float) $price : 0,
            ]);

            DB::commit();

            $this->info(sprintf(
                "Updated %d SKU(s) as outlet items with price €%s\n",
                count($skuIdsToUpdate),
                number_format(isset($price) ? (float) $price : 0, 2)
            ));

            return Command::SUCCESS;
        } catch (Exception $e) {
            DB::rollBack();
            $this->error('Error: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
