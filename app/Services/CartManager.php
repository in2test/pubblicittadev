<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * CartManager Service
 *
 * This service manages the shopping cart state using the Laravel session.
 * It treats every addition to the cart as a unique "Lavorazione" (Job),
 * ensuring that different customizations of the same product remain separate.
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
     * @return array<string, array> An associative array of cart items, keyed by their Job UUID.
     */
    public function getItems(): array
    {
        return Session::get(self::CART_KEY, []);
    }

    /**
     * Add a new job to the cart.
     *
     * Every call to add() creates a unique entry (Job), even if the product
     * configuration is identical to another item in the cart.
     *
     * @param  array  $item  The item data containing product_id, quantity, and configuration.
     */
    public function add(array $item): void
    {
        $items = $this->getItems();

        // Generate a unique Job ID for this specific addition
        $jobId = (string) Str::uuid();

        // Create new entry in the cart with the unique Job ID
        $items[$jobId] = array_merge($item, [
            'job_id' => $jobId,
            'quantity' => $item['quantity'] ?? 1,
            'created_at' => now()->toDateTimeString(),
        ]);

        Session::put(self::CART_KEY, $items);
    }

    /**
     * Update the quantity of a specific job in the cart.
     *
     * If the quantity is set to 0 or less, the job is removed from the cart.
     *
     * @param  string  $jobId  The unique UUID of the job in the cart.
     * @param  int  $quantity  The new quantity for the job.
     */
    public function update(string $jobId, int $quantity): void
    {
        $items = $this->getItems();

        if (isset($items[$jobId])) {
            if ($quantity <= 0) {
                unset($items[$jobId]);
            } else {
                $items[$jobId]['quantity'] = $quantity;
            }
        }

        Session::put(self::CART_KEY, $items);
    }

    /**
     * Remove a specific job from the cart.
     *
     * @param  string  $jobId  The unique UUID of the job to remove.
     */
    public function remove(string $jobId): void
    {
        $items = $this->getItems();
        unset($items[$jobId]);
        Session::put(self::CART_KEY, $items);
    }

    /**
     * Remove multiple jobs from the cart based on a list of IDs.
     *
     * @param  array  $jobIds  An array of Job UUIDs to be removed.
     */
    public function removeMultiple(array $jobIds): void
    {
        $items = $this->getItems();
        foreach ($jobIds as $jobId) {
            unset($items[$jobId]);
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
     * This method iterates through all jobs and recalculates the price
     * based on the latest product pricing and quantity discounts.
     * Since each job is unique, discounts are applied per-job.
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
                    // Discount is calculated for the quantity of THIS SPECIFIC job
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
}
