<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $categories = Category::all();
        $products = Product::where('is_active', true)
            ->with(['category', 'variations.color', 'media'])
            ->orderBy('name')
            ->paginate(12)
            ->appends($request->query());

        return view('categories', [
            'category' => $categories,
            'products' => $products,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $categoryIds = $category->children->pluck('id')->toArray();
        $categoryIds[] = $category->id;

        $products = Product::where('is_active', true)
            ->whereIn('category_id', $categoryIds)
            ->with(['category', 'variations.color', 'media'])
            ->orderBy('name')
            ->paginate(12)
            ->appends($request->query());

        return view('categories', [
            'category' => $category,
            'products' => $products,
        ]);
    }
}
