<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        return view('categories', [
            'category' => null,
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $category = Category::query()->where('slug', '=', $slug)->with('parent')->firstOrFail();

        return view('categories', [
            'category' => $category,
        ]);
    }
}
