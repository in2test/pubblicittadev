<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProductClass;
use App\Filament\Resources\Products\NewWaveProducts\NewWaveProductResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductAdminUrlService
{
    /**
     * Resolve the correct Filament edit page for a product.
     *
     * Products with an unsupported class intentionally return a safe fallback URL.
     * Filament can also throw when a panel is unavailable, so callers always receive a
     * usable string instead of an exception escaping into a catalogue view.
     */
    public function resolve(Product $product): string
    {
        try {
            // NewWave products use a separate resource with its own form and workflow.
            if ($product->type === Product::TYPE_NEWWAVE) {
                return NewWaveProductResource::getUrl('edit', ['record' => $product]);
            }

            // Standard product classes share the main product resource.
            return match ($product->product_class) {
                ProductClass::Apparel, ProductClass::AreaBased, ProductClass::ItemBased => ProductResource::getUrl('edit', ['record' => $product]),
                default => '#',
            };
        } catch (Throwable $exception) {
            Log::warning('Unable to resolve the product admin URL.', [
                'product_id' => $product->getKey(),
                'exception' => $exception::class,
            ]);

            // URL generation must not break customer-facing pages if the admin panel is unavailable.
            return '#';
        }
    }
}
