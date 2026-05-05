<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

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
    public function show(string $category, string $slug)
    {
        $productQuery = Product::where('slug', $slug)
            ->with([
                'category',
                'category.parent',
                'pricingTiers',
                'variations.color',
                'variations.size',
                'variations.printPlacement',
                'variations.printSide',
            ]);

        if (! $this->shouldShowInactiveProducts()) {
            $productQuery->where('is_active', true);
        }

        $product = $productQuery->firstOrFail();

        // Syncing is now handled via background jobs from the admin panel.

        $category = Category::where('slug', $category)->firstOrFail();

        return view('product', ['product' => $product, 'category' => $category]);
    }

    private function shouldShowInactiveProducts(): bool
    {
        return auth()->check() && auth()->user()?->isAdmin() === true;
    }
}
