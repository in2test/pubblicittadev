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
    public function show($category, $slug)
    {
        //

        $product = Product::where('slug', $slug)
            ->with(['category', 'category.parent', 'pricingTiers'])
            ->firstOrFail();

        $category = Category::where('slug', $category)->firstOrFail();

        return view('product', ['product' => $product, 'category' => $category]);
    }
}
