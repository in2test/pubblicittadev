<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Product;
use App\Services\GoogleMerchantFeedCache;

class ProductObserver
{
    /**
     * Handle the Product "saved" event.
     */
    public function saved(Product $product): void
    {
        // Ricalcola solo se sono cambiati i campi che influenzano il prezzo
        if ($product->wasRecentlyCreated || $product->wasChanged(['price', 'offer_price', 'product_class', 'min_area', 'allows_custom_size', 'min_custom_width', 'min_custom_height', 'is_active'])) {
            $product->updateCachedPrices();
        }

        $this->forgetCachedFeed();
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        $this->forgetCachedFeed();
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        $this->forgetCachedFeed();
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        $this->forgetCachedFeed();
    }

    /**
     * The cached feed no longer matches the catalogue once a product changes.
     */
    private function forgetCachedFeed(): void
    {
        app(GoogleMerchantFeedCache::class)->scheduleRefresh();
    }
}
