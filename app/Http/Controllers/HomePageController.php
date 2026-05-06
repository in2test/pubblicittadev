<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

/**
 * HomePageController manages the landing page and the initial product showcase.
 *
 * It handles the logic for selecting a mix of featured and latest products
 * to ensure the homepage always has a full and relevant product grid.
 */
class HomePageController extends Controller
{
    /**
     * Display the homepage.
     *
     * This method retrieves a set of products for the homepage. It prioritizes
     * 'featured' products first. If the number of featured products is less
     * than the required 9, it fills the remaining slots with the latest non-featured products.
     *
     * @return View The rendered welcome view.
     */
    public function index(): View
    {
        // 1. Fetch featured products first
        $featuredProducts = Product::where('is_active', true)
            ->where('is_featured', true)
            ->with(['category', 'variations.color', 'media'])
            ->latest()
            ->take(9)
            ->get();

        $count = $featuredProducts->count();

        // 2. If we have fewer than 9 featured products, fill the gap with latest non-featured products
        if ($count < 9) {
            $remainingCount = 9 - $count;
            $latestProducts = Product::where('is_active', true)
                ->where('is_featured', false)
                ->with(['category', 'variations.color', 'media'])
                ->latest()
                ->take($remainingCount)
                ->get();

            $products = $featuredProducts->merge($latestProducts);
        } else {
            $products = $featuredProducts;
        }

        return view('welcome', ['products' => $products]);
    }
}
