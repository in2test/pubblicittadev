<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard');
    }

    public function quotes(Request $request): View
    {
        $quotes = $request->user()->quotes()->with('items.product')->latest()->paginate(10);

        return view('dashboard.quotes', ['quotes' => $quotes]);
    }

    public function addresses(Request $request): View
    {
        $addresses = $request->user()->addresses()->latest()->get();

        return view('dashboard.addresses', ['addresses' => $addresses]);
    }
}
