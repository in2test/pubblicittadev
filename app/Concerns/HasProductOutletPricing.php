<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Services\ProductOutletService;

trait HasProductOutletPricing
{
    /** @var array<string, float> */
    private array $outletPriceCache = [];

    public function flushOutletPriceCache(): void
    {
        $this->outletPriceCache = [];
    }

    /**
     * @return array<string, float>
     */
    public function getOutletPricesPerOption(): array
    {
        if ($this->outletPriceCache !== []) {
            return $this->outletPriceCache;
        }

        return $this->outletPriceCache = app(ProductOutletService::class)->getOutletPricesPerOption($this);
    }

    public function getMinimumOutletPrice(): float
    {
        return app(ProductOutletService::class)->getMinimumOutletPrice($this);
    }

    public function hasValidOutletPrice(): bool
    {
        return app(ProductOutletService::class)->hasValidOutletPrice($this);
    }
}
