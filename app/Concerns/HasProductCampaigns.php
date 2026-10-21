<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Campaign;
use App\Models\ProductSku;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The campaigns that apply to a product and to its outlet SKUs.
 */
trait HasProductCampaigns
{
    /**
     * @return HasMany<ProductSku, $this>
     */
    public function activeOutletSkus(): HasMany
    {
        return $this->skus()
            ->where('is_outlet', true)
            ->whereHas('campaigns', function (Builder $q) {
                $now = CarbonImmutable::now();
                $q->where(function (Builder $sub) use ($now) {
                    $sub->whereNull('starts_at')
                        ->orWhere('starts_at', '<=', $now);
                })
                    ->where(function (Builder $sub) use ($now) {
                        $sub->whereNull('ends_at')
                            ->orWhere('ends_at', '>=', $now);
                    });
            });
    }

    public function hasActiveOfferCampaign(): bool
    {
        return $this->activeOfferCampaign() instanceof Campaign;
    }

    public function activeOfferCampaign(): ?Campaign
    {
        if ((float) $this->offer_price <= 0) {
            return null;
        }

        if ($this->relationLoaded('campaigns')) {
            return $this->campaigns->first(
                fn (Campaign $campaign): bool => $campaign->isActive(),
            );
        }

        return $this->campaigns()
            ->where(function (Builder $q) {
                $now = CarbonImmutable::now();
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) {
                $now = CarbonImmutable::now();
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->first();
    }

    public function activeOutletCampaign(): ?Campaign
    {
        if ($this->relationLoaded('skus')) {
            $sku = $this->skus->first(
                fn (ProductSku $s): bool => $s->isOutlet() && $s->hasActiveCampaign(),
            );

            return $sku?->activeCampaign();
        }

        return $this->activeOutletSkus()->with('campaigns')->first()?->activeCampaign();
    }
}
