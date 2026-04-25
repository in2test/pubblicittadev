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
        $products = Product::where('is_active', true)->with(['category', 'variations.color', 'media'])->get();

        return view('categories', [
            'category' => $categories,
            'products' => $products]);
    }

    /**
     * Display the specified resource.
     */
    public function show($slug)
    {

        $category = Category::where('slug', $slug)->first();
        $products = Product::where('is_active', true)->where('category_id', $category->id)->with(['category', 'variations.color', 'media'])->get();
        if ($category->children->count() > 0) {
            $products = Product::where('is_active', true)->whereIn('category_id', $category->children->pluck('id'))->with(['category', 'variations.color', 'media'])->get();
        }

        return view('categories', ['category' => $category, 'products' => $products]);
    }
}
