<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Session;

class CartManager
{
    private const string CART_KEY = 'cart_items';

    public function getItems(): array
    {
        return Session::get(self::CART_KEY, []);
    }

    public function add(array $item): void
    {
        $items = $this->getItems();
        $key = $this->generateKey($item);

        if (isset($items[$key])) {
            $items[$key]['quantity'] += $item['quantity'] ?? 1;
        } else {
            $items[$key] = array_merge($item, ['quantity' => $item['quantity'] ?? 1]);
        }

        Session::put(self::CART_KEY, $items);
    }

    public function update(string $key, int $quantity): void
    {
        $items = $this->getItems();

        if (isset($items[$key])) {
            if ($quantity <= 0) {
                unset($items[$key]);
            } else {
                $items[$key]['quantity'] = $quantity;
            }
        }

        Session::put(self::CART_KEY, $items);
    }

    public function remove(string $key): void
    {
        $items = $this->getItems();
        unset($items[$key]);
        Session::put(self::CART_KEY, $items);
    }

    public function clear(): void
    {
        Session::forget(self::CART_KEY);
    }

    public function count(): int
    {
        return array_sum(array_column($this->getItems(), 'quantity'));
    }

    public function total(): float
    {
        return array_sum(array_map(fn (array $item) => ($item['price'] ?? 0) * ($item['quantity'] ?? 1), $this->getItems()));
    }

    private function generateKey(array $item): string
    {
        return sprintf(
            '%d-%d-%d-%s',
            $item['product_id'],
            $item['color_id'] ?? 0,
            $item['size_id'] ?? 0,
            md5(serialize($item['print_placements'] ?? []))
        );
    }
}
