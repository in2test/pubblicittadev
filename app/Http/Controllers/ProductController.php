<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\SyncNewWaveProductJob;
use App\Models\Category;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Product $product): void
    {
        Product::query()->where('slug', $product->slug)->first();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $category, string $slug)
    {
        $product = Product::query()->visibleToCurrentUser()->where('slug', $slug)
            ->with([
                'category',
                'category.parent',
                'pricingTiers',
                'variations.color',
                'variations.size',
                'variations.printPlacement',
                'variations.printSide',
            ])
            ->firstOrFail();

        // Refresh availability data in the background if the last sync is stale (>12 h).
        if ($product->needsAvailabilityRefresh()) {
            SyncNewWaveProductJob::dispatch($product->id);
        }

        $category = Category::query()->where('slug', $category)->firstOrFail();

        return view('product', ['product' => $product, 'category' => $category]);
    }
}
