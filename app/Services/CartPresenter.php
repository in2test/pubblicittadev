<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;

class CartPresenter
{
    public function __construct(
        private readonly CartManager $cart,
        private readonly CartPresentationDataLoader $dataLoader,
        private readonly CartItemPresenter $itemPresenter,
    ) {}

    /**
     * Build the presented data for the cart view.
     *
     * @return array{
     *     items: array<string, array<string, mixed>>,
     *     total: float,
     *     count: int,
     *     totalSavings: float,
     *     totalQty: int
     * }
     */
    public function present(): array
    {
        /** @var Collection<string, array<string, mixed>> $rawItems */
        $rawItems = collect($this->cart->getItems());
        $products = $this->cart->getProducts();
        $relatedData = $this->dataLoader->load($rawItems, $products);
        $presentation = $this->itemPresenter->present($rawItems, $products, $relatedData);

        return [
            'items' => $presentation['items'],
            'total' => $this->cart->total(),
            'count' => $this->cart->count(),
            'totalSavings' => $presentation['total_savings'],
            'totalQty' => $presentation['total_qty'],
        ];
    }
}
