<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CategoryController handles the display and navigation of product categories.
 *
 * It provides the main catalog entry point and the detailed view for specific categories,
 * including the retrieval of child categories for hierarchical navigation.
 */
class CategoryController extends Controller
{
    /**
     * Display the main catalog page.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @return View The rendered catalog view.
     */
    public function index(Request $request): View
    {
        return view('categories', [
            'category' => null,
        ]);
    }

    /**
     * Display a specific category and its products.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  string  $slug  The unique slug of the category to retrieve.
     * @return View The rendered category view.
     *
     * @throws ModelNotFoundException If the category slug is not found.
     */
    public function show(Request $request, string $slug): View
    {
        $category = Category::query()->where('slug', '=', $slug, 'and')->with('parent')->firstOrFail();

        return view('categories', [
            'category' => $category,
        ]);
    }
}
