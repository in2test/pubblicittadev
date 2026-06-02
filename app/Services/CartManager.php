<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
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
     * @var Collection<int, Product>|null
     */
    protected ?Collection $products = null;

    /**
     * Retrieve all items currently in the cart.
     *
     * @return array<string, array<string, mixed>> An associative array of cart items, keyed by their Job UUID.
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
     * @param  array<string, mixed>  $item  The item data containing product_id, quantity, and configuration.
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
        $this->products = null;
    }

    public function update(string $jobId, int $quantity): void
    {
        $this->updateItemQuantity($jobId, $quantity);
    }

    /**
     * Update the quantity of a specific job in the cart.
     * Supports both single-size and multi-size (via skuId) updates.
     */
    public function updateItemQuantity(string $jobId, int $quantity, ?int $skuId = null): void
    {
        $items = $this->getItems();

        if (! isset($items[$jobId])) {
            return;
        }

        $item = $items[$jobId];

        if ($skuId !== null) {
            $item['quantities'] ??= [];
            $item['quantities'][$skuId] = $quantity;
            $item['quantity'] = array_sum($item['quantities']);
        } else {
            $item['quantity'] = $quantity;
        }

        if ($item['quantity'] <= 0) {
            $this->remove($jobId);

            return;
        }

        $this->replace($jobId, $item);
        $this->products = null;
    }

    /**
     * Update an existing job in the cart with a new configuration.
     *
     * This allows modifying the product, color, placements, and quantities
     * for a specific job without changing its UUID.
     *
     * @param  string  $jobId  The unique UUID of the job to update.
     * @param  array<string, mixed>  $item  The new item data.
     */
    public function replace(string $jobId, array $item): void
    {
        $items = $this->getItems();

        if (! isset($items[$jobId])) {
            $this->add($item);

            return;
        }

        $item['job_id'] = $jobId;
        $item['created_at'] = $items[$jobId]['created_at'] ?? now()->toDateTimeString();
        $items[$jobId] = $item;

        Session::put(self::CART_KEY, $items);
        $this->products = null;
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
        $this->products = null;
    }

    /**
     * Remove multiple jobs from the cart based on a list of IDs.
     *
     * @param  array<int|string, string>  $jobIds  An array of Job UUIDs to be removed.
     */
    public function removeMultiple(array $jobIds): void
    {
        $items = $this->getItems();
        foreach ($jobIds as $jobId) {
            unset($items[$jobId]);
        }
        Session::put(self::CART_KEY, $items);
        $this->products = null;
    }

    /**
     * Completely clear all items from the shopping cart.
     */
    public function clear(): void
    {
        Session::forget(self::CART_KEY);
        $this->products = null;
    }

    /**
     * Calculate the total number of items in the cart.
     *
     * @return int Total quantity of all products.
     */
    public function count(): int
    {
        return (int) collect($this->getItems())->sum(fn (array $item) => $this->getItemQuantity($item));
    }

    /**
     * Get the total quantity for a single cart item.
     *
     * @param  array<string, mixed>  $item
     */
    public function getItemQuantity(array $item): int
    {
        if (isset($item['quantities']) && is_array($item['quantities'])) {
            return (int) array_sum($item['quantities']);
        }

        return (int) ($item['quantity'] ?? 1);
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
        $items = $this->getItems();
        $products = $this->getProducts();

        $total = collect($items)->sum(function (array $item) use ($products): float {
            $qty = $this->getItemQuantity($item);

            // Fallback price used if the product has been deleted or is otherwise unavailable
            $price = (float) ($item['price'] ?? 0);

            // If the product still exists in the database, calculate its exact total dynamically
            if (! empty($item['product_id']) && $product = $products->get((int) $item['product_id'])) {

                // Calculate dynamic price based on:
                // 1. Quantity discounts (pricing tiers)
                // 2. Custom dimensions (width/height for banners, etc.)
                // 3. Selected modifier options (e.g., premium finishes)
                $totalPrice = $product->calculateTotalPrice(
                    $qty,
                    $item['quantities'] ?? [],
                    isset($item['width']) ? (float) $item['width'] : null,
                    isset($item['height']) ? (float) $item['height'] : null,
                    $item['selected_options'] ?? []
                );

                // Safeguard to ensure the calculated total price is never negative
                return max(0.0, $totalPrice);
            }

            // Fallback: multiply the static cart price by the total quantity
            return $price * max(0, $qty);
        });

        return round((float) $total, 2);
    }

    /**
     * Get the products in the cart with their relations, caching them in memory.
     *
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        if ($this->products instanceof Collection) {
            return $this->products;
        }

        $items = $this->getItems();
        $productIds = collect($items)->pluck('product_id')->filter()->unique();

        if ($productIds->isEmpty()) {
            return $this->products = collect();
        }

        return $this->products = Product::with([
            'images',
            'category',
            'skus.options',
            'variationTypes',
            'pricingTiers',
            'media',
        ])->whereIn('id', $productIds)->get()->keyBy('id');
    }

    /**
     * Check if the cart is empty.
     *
     * @return bool True if there are no items in the cart.
     */
    public function isEmpty(): bool
    {
        return $this->getItems() === [];
    }
}
