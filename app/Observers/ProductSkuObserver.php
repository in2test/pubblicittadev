<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ProductSku;
use App\Services\GoogleMerchantFeedCache;

class ProductSkuObserver
{
    /**
     * Handle the ProductSku "saved" event.
     */
    public function saved(ProductSku $productSku): void
    {
        if ($productSku->wasRecentlyCreated || $productSku->wasChanged(['override_price'])) {
            $productSku->product->updateCachedPrices();
        }

        $this->scheduleFeedRefresh();
    }

    /**
     * Handle the ProductSku "deleted" event.
     */
    public function deleted(ProductSku $productSku): void
    {
        if ($productSku->override_price !== null) {
            $productSku->product->updateCachedPrices();
        }

        $this->scheduleFeedRefresh();
    }

    /**
     * Handle the ProductSku "restored" event.
     */
    public function restored(ProductSku $productSku): void
    {
        $this->scheduleFeedRefresh();
    }

    /**
     * Handle the ProductSku "force deleted" event.
     */
    public function forceDeleted(ProductSku $productSku): void
    {
        $this->scheduleFeedRefresh();
    }

    /**
     * The feed lists one item per SKU, so a SKU change alters the feed.
     */
    private function scheduleFeedRefresh(): void
    {
        app(GoogleMerchantFeedCache::class)->scheduleRefresh();
    }
}
