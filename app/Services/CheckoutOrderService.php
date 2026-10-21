<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingTier;
use App\Models\User;
use App\Support\CheckoutOrderDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutOrderService
{
    public function __construct(private readonly CartManager $cartManager) {}

    /**
     * @param  array<string, array<string, mixed>>  $items
     */
    public function createFromCart(
        Request $request,
        array $items,
        ?int $shippingId = null,
        ?int $billingId = null,
        bool $isQuotation = false,
    ): Order {
        $productIds = collect($items)->pluck('product_id')->filter()->unique();
        $products = $productIds->isEmpty()
            ? collect()
            : Product::with(['variationTypes', 'skus.options', 'pricingTiers', 'media'])
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

        /** @var User $user */
        $user = $request->user();

        $details = new CheckoutOrderDetails(
            itemsTotal: $this->cartManager->total(),
            shippingCost: $this->shippingCost(
                $request->input('shipping_method', 'delivery'),
                $this->cartManager->total(),
            ),
            shippingMethod: (string) $request->input('shipping_method', 'delivery'),
            shippingAddressId: $shippingId,
            billingAddressId: $billingId,
            isQuotation: $isQuotation,
        );

        return DB::transaction(fn (): Order => $this->persistOrderFromCart(
            $request,
            $items,
            $details,
            $products,
            $user,
        ));
    }

    /**
     * @param  array<string, array<string, mixed>>  $items
     * @param  Collection<int, Product>  $products
     */
    private function persistOrderFromCart(
        Request $request,
        array $items,
        CheckoutOrderDetails $details,
        Collection $products,
        User $user,
    ): Order {
        $order = $this->createOrderRecord($request, $user, $details);

        foreach ($items as $item) {
            $product = $products->get((int) $item['product_id']);
            if (! $product instanceof Product) {
                continue;
            }

            $this->createOrderItem($order, $product, $item);
        }

        return $order;
    }

    private function shippingCost(mixed $shippingMethod, float $itemsTotal): float
    {
        if ($shippingMethod !== 'delivery') {
            return 0.00;
        }

        $tier = ShippingTier::where('min_order_total', '<=', $itemsTotal)
            ->orderBy('min_order_total', 'desc')
            ->first();

        return $tier ? (float) $tier->shipping_cost : 0.00;
    }

    private function createOrderRecord(
        Request $request,
        User $user,
        CheckoutOrderDetails $details,
    ): Order {
        return Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-'.strtoupper((string) Str::ulid()),
            'payment_status' => $details->isQuotation ? 'quotation' : 'pending',
            'work_status' => 'pending',
            'items_total' => $details->itemsTotal,
            'shipping_cost' => $details->shippingCost,
            'shipping_method' => $details->shippingMethod,
            'total_price' => $details->total(),
            'total_items' => $this->cartManager->count(),
            'shipping_address_id' => $details->shippingAddressId ?? $request->input('shipping_address_id'),
            'billing_address_id' => $details->billingAddressId ?? $request->input('billing_address_id'),
            'notes' => ($details->shippingMethod === 'pickup' ? "[Ritiro in negozio]\n" : '').$request->input('notes'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function createOrderItem(Order $order, Product $product, array $item): void
    {
        $quantity = $this->cartManager->getItemQuantity($item);
        $subtotal = $product->calculateTotalPrice(
            $quantity,
            $item['quantities'] ?? [],
            isset($item['width']) ? (float) $item['width'] : null,
            isset($item['height']) ? (float) $item['height'] : null,
            $item['selected_options'] ?? [],
        );
        $unitPrice = $quantity > 0 ? $subtotal / $quantity : 0.0;
        $hasPersonalization = $this->hasModifierOption($item, $product)
            || ! empty($item['design_file_path']);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'customization_json' => $item,
            'work_status' => $hasPersonalization ? 'awaiting_file' : 'pending',
        ]);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function hasModifierOption(array $item, Product $product): bool
    {
        if (empty($item['selected_options'])) {
            return false;
        }

        $modifierTypeIds = $product->variationTypes()
            ->wherePivot('is_modifier', true)
            ->pluck('variation_types.id')
            ->toArray();

        foreach ($item['selected_options'] as $typeId => $optionIds) {
            if (in_array((int) $typeId, $modifierTypeIds)) {
                return true;
            }
        }

        return false;
    }
}
