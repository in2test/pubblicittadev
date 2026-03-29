<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;

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
    public function show(Category $category)
    {
        $category = Category::where('slug', $category->slug)->first();

        return view('category', ['category' => $category]);
    }
}
