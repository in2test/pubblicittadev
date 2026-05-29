<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\PricingTier;

class PricingTierObserver
{
    /**
     * Handle the PricingTier "saved" event.
     */
    public function saved(PricingTier $pricingTier): void
    {
        $pricingTier->product->updateCachedPrices();
    }

    /**
     * Handle the PricingTier "deleted" event.
     */
    public function deleted(PricingTier $pricingTier): void
    {
        $pricingTier->product->updateCachedPrices();
    }

    /**
     * Handle the PricingTier "restored" event.
     */
    public function restored(PricingTier $pricingTier): void
    {
        //
    }

    /**
     * Handle the PricingTier "force deleted" event.
     */
    public function forceDeleted(PricingTier $pricingTier): void
    {
        //
    }
}
