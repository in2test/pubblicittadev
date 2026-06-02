<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PortfolioItem;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function index(): View
    {
        $portfolioItems = PortfolioItem::where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('portfolio', ['portfolioItems' => $portfolioItems]);
    }
}
