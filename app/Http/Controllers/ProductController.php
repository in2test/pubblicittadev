<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Product Controller
 *
 * Handles the public-facing product detail page.
 */
class ProductController extends Controller
{
    /**
     * Display the specified product detail page.
     */
    public function show(Category $category, Product $product, Request $request): View
    {
        $product->load([
            'category.parent',
            'pricingTiers',
            'variationTypes.options',
            'skus.options.type',
            'media',
        ]);

        if (! $product->is_active && ! auth()->user()?->isAdmin()) {
            abort(404);
        }

        // If NewWave product and last update > 12 hours ago, fast sync availability
        if ($product->type === Product::TYPE_NEWWAVE && $product->updated_at?->diffInHours(now()) >= 12) {
            try {
                app(ProductAvailabilityService::class)->syncAvailability($product);
            } catch (Exception $e) {
                Log::warning("Failed to fast sync availability for product {$product->slug}: ".$e->getMessage());
            }
        }

        return view('product', [
            'product' => $product,
            'category' => $category,
            'jobId' => $request->query('job_id'),
        ]);
    }
}
