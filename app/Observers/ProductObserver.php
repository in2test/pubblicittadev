<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Product;

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
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        //
    }
}
