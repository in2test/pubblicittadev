<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductAvailabilityService;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Product $product): void
    {
        Product::where('slug', $product->slug)->first();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $category, string $slug, ProductAvailabilityService $availabilityService)
    {
        $product = Product::where('slug', $slug)
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

        // Sync availability from API on each show
        $availabilityService->syncProduct($product);

        $category = Category::where('slug', $category)->firstOrFail();

        return view('product', ['product' => $product, 'category' => $category]);
    }
}
