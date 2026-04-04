<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();

        return view('categories', ['categories' => $categories]);
    }

    /**
     * Display the specified resource.
     */
    public function show($slug)
    {
        $category = Category::where('slug', $slug)->first();
        $products = Product::where('category_id', $category->id)->with(['category', 'images'])->get();
        if ($category->children->count() > 0) {
            $products = Product::whereIn('category_id', $category->children->pluck('id'))->with(['category', 'images'])->get();
        }

        return view('categories', ['category' => $category, 'products' => $products]);
    }
}
