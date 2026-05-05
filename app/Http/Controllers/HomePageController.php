<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;

class HomePageController extends Controller
{
    public function index()
    {
        $products = Product::where('is_active', '=', true, 'and')->where('is_featured', '=', true, 'and')->with(['category', 'variations.color', 'media'])->latest()->take(9)->get();
        $count = $products->count();

        // If not enough featured products, take latest ones
        $products = $products->merge(Product::where('is_active', '=', true, 'and')->where('is_featured', '=', false, 'and')->with(['category', 'variations.color', 'media'])->latest()->take(9 - $count)->get());

        return view('welcome', ['products' => $products]);
    }
}
