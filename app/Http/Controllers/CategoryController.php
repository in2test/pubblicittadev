<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
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
     * @param  Category  $category  The category model (automatically bound by slug).
     * @return View The rendered category view.
     */
    public function show(Category $category): View
    {
        $category->load('parent');

        return view('categories', ['category' => $category]);
    }
}
