<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;

class HomePageController extends Controller
{
    public function index()
    {
        $products = Product::where('is_featured', true)->with('images', 'category')->latest()->take(12)->get();
        $count = $products->count();

        // If no featured products, take latest ones

        $products = $products->merge(Product::where('is_featured', false)-> with('images', 'category')->latest()->take(12 - $count)->get());

        return view('welcome', ['products' => $products]);
    }
}
