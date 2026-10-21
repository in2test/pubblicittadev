<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The details of an order that is about to be created.
 *
 * Groups the values that describe the order itself, so the checkout service
 * does not need to pass them around one by one.
 */
final readonly class CheckoutOrderDetails
{
    public function __construct(
        public float $itemsTotal = 0.0,
        public float $shippingCost = 0.0,
        public string $shippingMethod = 'delivery',
        public ?int $shippingAddressId = null,
        public ?int $billingAddressId = null,
        public bool $isQuotation = false,
    ) {}

    /**
     * The total the customer pays.
     */
    public function total(): float
    {
        return $this->itemsTotal + $this->shippingCost;
    }
}
