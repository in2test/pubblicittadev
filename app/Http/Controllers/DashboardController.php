<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard');
    }

    public function addresses(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $addresses = $user->addresses()->latest()->get();

        return view('dashboard.addresses', ['addresses' => $addresses]);
    }

    public function orders(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $orders = $user->orders()->with('items.product')->latest()->paginate(10);

        return view('dashboard.orders', ['orders' => $orders]);
    }

    public function showOrder(Request $request, Order $order): View
    {
        /** @var User $user */
        $user = $request->user();

        if ($order->user_id !== $user->id) {
            abort(403);
        }

        $order->load(['items.product', 'shippingAddress', 'billingAddress']);

        return view('dashboard.order-details', ['order' => $order]);
    }
}
