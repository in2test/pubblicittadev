<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     *
     * @param  string  $category  The category slug from the URL
     * @param  string  $slug  The product slug from the URL
     * @param  Request  $request  The incoming HTTP request
     */
    public function show(string $category, string $slug, Request $request): View
    {
        // Fetch the product with all necessary relationships for the detail page
        $productQuery = Product::where('slug', '=', $slug, 'and')
            ->with([
                'category',
                'category.parent',
                'pricingTiers',
                'variations.color',
                'variations.size',
                'variations.printPlacement',
                'variations.printSide',
                'media',
            ]);

        // Authorization: Only admins can view inactive products
        if (! $this->shouldShowInactiveProducts()) {
            $productQuery->where('is_active', '=', true, 'and');
        }

        $product = $productQuery->firstOrFail();

        // If NewWave product and last update > 12 hours ago, fast sync availability
        if ($product->type === Product::TYPE_NEWWAVE && $product->updated_at && $product->updated_at->diffInHours(now()) >= 12) {
            try {
                $availabilityService = app(ProductAvailabilityService::class);
                $availabilityService->syncAvailability($product);
            } catch (Exception $e) {
                Log::warning("Failed to fast sync availability for product {$product->slug}: ".$e->getMessage());
                // Silently fail and use existing data
            }
        }

        $category = Category::where('slug', '=', $category, 'and')->firstOrFail();
        $colorId = $request->query('color_id') ?? null;
        $jobId = $request->query('job_id') ?? null;

        return view('product', [
            'product' => $product,
            'category' => $category,
            'colorId' => $colorId,
            'jobId' => $jobId,
        ]);
    }

    /**
     * Helper to determine if inactive products should be visible to the current user
     */
    private function shouldShowInactiveProducts(): bool
    {
        return Auth::check() && Auth::user()?->isAdmin() === true;
    }
}
