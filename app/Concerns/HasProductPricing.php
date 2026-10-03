<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\CategoryQuantityDiscount;
use App\Models\ProductSku;
use App\Services\ProductPriceCalculator;
use App\Services\ProductPricingService;
use App\Services\ProductStartingPriceService;
use App\Services\ProductVariantResolver;
use App\Services\QuantityDiscountService;
use Illuminate\Support\Collection;

trait HasProductPricing
{
    /** @var Collection<int, CategoryQuantityDiscount>|null */
    private ?Collection $quantityDiscountsCache = null;

    /** @var array<string, float|null> */
    private array $priceCache = [];

    /** @var array<string, float|null> */
    private array $tierPriceCache = [];

    /**
     * @return array{price: float, base_price: float, is_discounted: bool, on_request: bool}
     */
    public function getDisplayPriceData(int $quantity = 1): array
    {
        $discountedPrice = $this->getPriceForQuantity($quantity);
        $basePrice = (float) $this->price;

        return [
            'price' => $discountedPrice,
            'base_price' => $basePrice,
            'is_discounted' => $discountedPrice > 0 && $discountedPrice < $basePrice,
            'on_request' => $basePrice <= 0 && $discountedPrice <= 0,
        ];
    }

    public function getPriceForQuantity(int $quantity = 1, ?ProductSku $sku = null): float
    {
        $cacheKey = $quantity.'_'.($sku->id ?? 'null');
        if (array_key_exists($cacheKey, $this->priceCache)) {
            return (float) $this->priceCache[$cacheKey];
        }

        $price = app(ProductPricingService::class)->getPriceForQuantity($this, $quantity, $sku);

        return $this->priceCache[$cacheKey] = $price;
    }

    public function getTierPrice(int $quantity, ?ProductSku $sku = null): ?float
    {
        $cacheKey = $quantity.'_'.($sku->id ?? 'null');
        if (array_key_exists($cacheKey, $this->tierPriceCache)) {
            return $this->tierPriceCache[$cacheKey];
        }

        $tierPrice = app(ProductPricingService::class)->getTierPrice($this, $quantity, $sku);

        return $this->tierPriceCache[$cacheKey] = $tierPrice;
    }

    /**
     * @return array{sheets: int, sheets_x: int, sheets_y: int, exceeds: bool}
     */
    public function getSheetsNeeded(float $width, float $height): array
    {
        return app(ProductPriceCalculator::class)->getSheetsNeeded($this, $width, $height);
    }

    public function calculateItemsPerSheet(float $itemWidth, float $itemHeight): int
    {
        return app(ProductPriceCalculator::class)->calculateItemsPerSheet($this, $itemWidth, $itemHeight);
    }

    public function calculateTotalBilledArea(int $quantity, float $width, float $height): float
    {
        return app(ProductPriceCalculator::class)->calculateTotalBilledArea($this, $quantity, $width, $height);
    }

    /**
     * @param  array<int, int|array<int>>  $selectedOptions
     */
    public function getActiveSku(array $selectedOptions): ?ProductSku
    {
        return app(ProductVariantResolver::class)->getActiveSku($this, $selectedOptions);
    }

    /**
     * @param  array<int, int>  $skuQuantities
     * @param  array<int, int|array<int>>  $selectedOptions
     */
    public function calculateTotalPrice(
        int $totalQuantity,
        array $skuQuantities = [],
        ?float $width = null,
        ?float $height = null,
        array $selectedOptions = [],
    ): float {
        return app(ProductPriceCalculator::class)->calculateTotalPrice(
            $this,
            $totalQuantity,
            $skuQuantities,
            $width,
            $height,
            $selectedOptions,
        );
    }

    public function getNearestFormatOptionId(float $width, float $height): ?int
    {
        return app(ProductVariantResolver::class)->getNearestFormatOptionId($this, $width, $height);
    }

    /**
     * @param  array<int, int|array<int>>  $selectedOptions
     */
    public function applyModifiersToTotal(float $total, int $totalQuantity, array $selectedOptions): float
    {
        return app(ProductPriceCalculator::class)->applyModifiersToTotal($this, $total, $totalQuantity, $selectedOptions);
    }

    public function calculateFinalUnitPrice(
        int $quantity,
        ?float $width = null,
        ?float $height = null,
        ?ProductSku $sku = null,
    ): float {
        return app(ProductPriceCalculator::class)->calculateFinalUnitPrice($this, $quantity, $width, $height, $sku);
    }

    public function getMinimumOrderQuantity(): int
    {
        return app(ProductStartingPriceService::class)->getMinimumOrderQuantity($this);
    }

    public function getStartingPrice(bool $isOutlet = false): float
    {
        return app(ProductStartingPriceService::class)->getStartingPrice($this, $isOutlet);
    }

    public function getAbsoluteMinimumPrice(bool $skipCache = false, bool $isOutlet = false): float
    {
        return app(ProductStartingPriceService::class)->getAbsoluteMinimumPrice($this, $skipCache, $isOutlet);
    }

    public function getStartingUnitPrice(bool $skipCache = false, bool $isOutlet = false): float
    {
        return app(ProductStartingPriceService::class)->getStartingUnitPrice($this, $skipCache, $isOutlet);
    }

    /**
     * @return Collection<int, CategoryQuantityDiscount>
     */
    public function getQuantityDiscounts(): Collection
    {
        if ($this->quantityDiscountsCache instanceof Collection) {
            return $this->quantityDiscountsCache;
        }

        return $this->quantityDiscountsCache = app(QuantityDiscountService::class)
            ->getQuantityDiscountsForProduct($this);
    }

    public function updateCachedPrices(): void
    {
        app(ProductStartingPriceService::class)->updateCachedPrices($this);
    }
}
