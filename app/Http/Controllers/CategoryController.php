<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $categories = Category::all();

        if ($search) {
            $products = Product::search($search)
                ->where('is_active', true)
                ->query(fn ($query) => $query->with(['category', 'variations.color', 'media']))
                ->orderBy('name')
                ->paginate(12)
                ->appends($request->query());
        } else {
            $products = Product::where('is_active', true)
                ->with(['category', 'variations.color', 'media'])
                ->orderBy('name')
                ->paginate(12)
                ->appends($request->query());
        }

        return view('categories', [
            'category' => $categories,
            'products' => $products,
            'search' => $search,
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $search = $request->input('search', '');

        $categoryIds = $category->children->pluck('id')->toArray();
        $categoryIds[] = $category->id;

        if ($search) {
            $products = Product::search($search)
                ->where('is_active', true)
                ->whereIn('category_id', $categoryIds)
                ->query(fn ($query) => $query->with(['category', 'variations.color', 'media']))
                ->orderBy('name')
                ->paginate(12)
                ->appends($request->query());
        } else {
            $products = Product::where('is_active', true)
                ->whereIn('category_id', $categoryIds)
                ->with(['category', 'variations.color', 'media'])
                ->orderBy('name')
                ->paginate(12)
                ->appends($request->query());
        }

        return view('categories', [
            'category' => $category,
            'products' => $products,
            'search' => $search,
        ]);
    }
}
