<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Product $product)
    {
        $product = Product::where('slug', $product->slug)->first();
    }

    /**
     * Display the specified resource.
     */
    public function show($category, $slug)
    {
        //

        $product = Product::where('slug', $slug)->with('category', 'category.parent', 'images')->first();
        $category = Category::where('slug', $category)->first();
        return view('product', ['product' => $product, 'category' => $category]);
    }
}
