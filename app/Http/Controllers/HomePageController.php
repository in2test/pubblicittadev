<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;

class HomePageController extends Controller
{
    public function index()
    {
        $products = Product::where('is_active', true)->where('is_featured', true)->with(['category', 'variations.color', 'media'])->latest()->take(12)->get();
        $count = $products->count();

        // If not enough featured products, take latest ones
        $products = $products->merge(Product::where('is_active', true)->where('is_featured', false)->with(['category', 'variations.color', 'media'])->latest()->take(12 - $count)->get());

        return view('welcome', ['products' => $products]);
    }
}
