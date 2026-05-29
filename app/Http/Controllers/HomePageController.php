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
        $products = Product::active()
            ->with(['category', 'variationTypes', 'productVariationTypes.options.option', 'media', 'images'])
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->take(9)
            ->get();

        return view('welcome', ['products' => $products]);
    }
}
