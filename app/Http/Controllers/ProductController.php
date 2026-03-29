<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Product $product)
    {
        $product = Product::where('slug', $product->slug)->first();

        return view('product', ['product' => $product]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): void
    {
        //
    }
}
