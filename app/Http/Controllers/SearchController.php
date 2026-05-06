<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SearchController handles the processing of search queries from the frontend.
 *
 * It acts as a bridge between the global search form and the Catalog Livewire component,
 * ensuring that search terms and category contexts are correctly passed to the view.
 */
class SearchController extends Controller
{
    /**
     * Handle the search request and render the catalog.
     *
     * This method captures the search keyword 'q' and the optional 'category' slug,
     * then returns the specialized search view that embeds the Catalog component.
     *
     * @param  Request  $request  The incoming HTTP request containing search parameters.
     * @return View The rendered search results view.
     */
    public function index(Request $request): View
    {
        $query = $request->query('q', '');
        $category = $request->query('category', '');

        return view('catalog-search', [
            'search' => $query,
            'category' => $category,
        ]);
    }
}
