<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ProductSku;

class ProductSkuObserver
{
    /**
     * Handle the ProductSku "saved" event.
     */
    public function saved(ProductSku $productSku): void
    {
        if ($productSku->wasRecentlyCreated || $productSku->wasChanged(['override_price'])) {
            $productSku->product?->updateCachedPrices();
        }
    }

    /**
     * Handle the ProductSku "deleted" event.
     */
    public function deleted(ProductSku $productSku): void
    {
        if ($productSku->override_price !== null) {
            $productSku->product?->updateCachedPrices();
        }
    }

    /**
     * Handle the ProductSku "restored" event.
     */
    public function restored(ProductSku $productSku): void
    {
        //
    }

    /**
     * Handle the ProductSku "force deleted" event.
     */
    public function forceDeleted(ProductSku $productSku): void
    {
        //
    }
}
