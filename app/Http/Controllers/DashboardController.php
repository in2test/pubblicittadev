<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
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

    public function orders(Request $request): View
    {
        $orders = $request->user()->orders()->with('items.product')->latest()->paginate(10);

        return view('dashboard.orders', ['orders' => $orders]);
    }

    public function showOrder(Request $request, Order $order): View
    {
        // Ensure the order belongs to the user
        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }

        $order->load(['items.product', 'shippingAddress', 'billingAddress']);

        return view('dashboard.order-details', ['order' => $order]);
    }
}
