<?php

namespace App\Http\Controllers;
 
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
 
class HomePageController extends Controller
{
    public function index()
    {
        $categories = Category::whereNull('parent_id')->with('image')->get();
        $products = Product::where('is_featured', true)->with('images')->latest()->take(8)->get();
        
        // If no featured products, take latest ones
        if ($products->isEmpty()) {
            $products = Product::with('images')->latest()->take(4)->get();
        }

        return view('welcome', compact('categories', 'products'));
    }
}
