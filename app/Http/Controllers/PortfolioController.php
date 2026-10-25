<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PortfolioItem;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function index(): View
    {
        $portfolioItems = PortfolioItem::query()
            ->with('products')
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('portfolio', ['portfolioItems' => $portfolioItems]);
    }

    public function show(PortfolioItem $portfolioItem): View
    {
        $portfolioItem->load(['media', 'products.media', 'products.images', 'products.category']);

        return view('portfolio.show', ['portfolioItem' => $portfolioItem]);
    }
}
