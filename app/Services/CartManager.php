<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Session;

/**
 * CartManager Service
 *
 * This service manages the shopping cart state using the Laravel session.
 * It handles the addition, updating, and removal of items, and calculates
 * the total cart value by integrating with product pricing and quantity discounts.
 */
class CartManager
{
    /**
     * The session key used to store cart items.
     */
    private const string CART_KEY = 'cart_items';

    /**
     * Retrieve all items currently in the cart.
     *
     * @return array<string, array> An associative array of cart items, keyed by their unique hash.
     */
    public function getItems(): array
    {
        return Session::get(self::CART_KEY, []);
    }

    /**
     * Add an item to the cart or increase the quantity if it already exists.
     *
     * @param  array  $item  The item data containing product_id, quantity, and configuration.
     */
    public function add(array $item): void
    {
        $items = $this->getItems();
        $key = $this->generateKey($item);

        if (isset($items[$key])) {
            // Item already exists in cart, just update the quantity
            $items[$key]['quantity'] += $item['quantity'] ?? 1;

            // Update color details if they were changed
            if (! empty($item['color_id'])) {
                $items[$key]['color_id'] = $item['color_id'];
            }
            if (! empty($item['color_name'])) {
                $items[$key]['color_name'] = $item['color_name'];
            }
        } else {
            // Create new entry in the cart
            $items[$key] = array_merge($item, ['quantity' => $item['quantity'] ?? 1]);
        }

        Session::put(self::CART_KEY, $items);
    }

    /**
     * Update the quantity of a specific item in the cart.
     *
     * If the quantity is set to 0 or less, the item is removed from the cart.
     *
     * @param  string  $key  The unique key of the item in the cart.
     * @param  int  $quantity  The new quantity for the item.
     */
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

    /**
     * Remove a specific item from the cart.
     *
     * @param  string  $key  The unique key of the item to remove.
     */
    public function remove(string $key): void
    {
        $items = $this->getItems();
        unset($items[$key]);
        Session::put(self::CART_KEY, $items);
    }

    /**
     * Remove multiple items from the cart based on a list of keys.
     *
     * @param  array  $keys  An array of keys to be removed.
     */
    public function removeMultiple(array $keys): void
    {
        $items = $this->getItems();
        foreach ($keys as $key) {
            unset($items[$key]);
        }
        Session::put(self::CART_KEY, $items);
    }

    /**
     * Completely clear all items from the shopping cart.
     */
    public function clear(): void
    {
        Session::forget(self::CART_KEY);
    }

    /**
     * Calculate the total number of items in the cart.
     *
     * @return int Total quantity of all products.
     */
    public function count(): int
    {
        return array_sum(array_column($this->getItems(), 'quantity'));
    }

    /**
     * Calculate the total monetary value of the cart.
     *
     * This method iterates through all items and recalculates the price
     * based on the latest product pricing and quantity discounts to ensure
     * the total is always accurate at the moment of calculation.
     *
     * @return float The total value of the cart, formatted to 2 decimal places.
     */
    public function total(): float
    {
        $total = 0.0;
        foreach ($this->getItems() as $item) {
            $price = (float) ($item['price'] ?? 0);

            // Recalculate price based on current product state and quantity discounts
            if (! empty($item['product_id'])) {
                $product = Product::find((int) $item['product_id']);
                if ($product) {
                    $disc = $product->getPriceForQuantity((int) ($item['quantity'] ?? 1));
                    if ($disc > 0) {
                        $price = (float) $disc;
                    }
                }
            }

            $qty = (int) ($item['quantity'] ?? 1);
            $total += $price * max(0, $qty);
        }

        return (float) number_format($total, 2, '.', '');
    }

    /**
     * Generate a unique hash key for a cart item.
     *
     * The key is based on product ID, color, size, and the serialized print placements.
     * This ensures that identical configurations are merged into a single cart item
     * while different configurations are treated as separate items.
     *
     * @param  array  $item  The item configuration data.
     * @return string A unique identifier for the item configuration.
     */
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
