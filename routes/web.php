<?php

declare(strict_types=1);

use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// template routes for inspiration purposes only, these will be replaced with dynamic routes in the future

Route::view('/services', 'services')->name('services');
Route::view('/contact', 'contact')->name('contact');
Route::view('/palette', 'palette')->name('palette');

// Cart routes
Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::put('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart/remove-multiple', [CartController::class, 'removeMultiple'])->name('cart.removeMultiple');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
Route::get('/cart/price', [CartController::class, 'price'])->name('cart.price');

Route::get('/', [HomePageController::class, 'index'])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/addresses', [DashboardController::class, 'addresses'])->name('dashboard.addresses');
    Route::get('/dashboard/orders', [DashboardController::class, 'orders'])->name('dashboard.orders');
    Route::get('/dashboard/orders/{order}', [DashboardController::class, 'showOrder'])->name('dashboard.orders.show');
    Route::post('/logout', function (): Redirector|RedirectResponse {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->name('logout');

    Route::post('/admin/products/{product}/toggle-active', [AdminProductController::class, 'toggleActive'])
        ->name('admin.products.toggle-active');

    Route::post('/admin/products/{product}/sync', [AdminProductController::class, 'sync'])
        ->name('admin.products.sync');
});

require __DIR__.'/settings.php';

Route::middleware(['auth'])->group(function () {
    Volt::route('/checkout', 'pages.checkout')->name('checkout');
    Route::post('/checkout/session', [CheckoutController::class, 'createSession'])->name('checkout.session');
    Route::post('/checkout/quotation', [CheckoutController::class, 'requestQuotation'])->name('checkout.quotation');
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
});

// Webhook route (excluded from CSRF in bootstrap/app.php)
Route::post('/webhooks/stripe', [WebhookController::class, 'handle'])->name('webhooks.stripe');

// dynamic routes for products and categories
Route::get('/catalogo', [CategoryController::class, 'index'])->name('catalog');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/catalogo/{category:slug}', [CategoryController::class, 'show'])->name('category');
Route::get('/catalogo/{category:slug}/{product:slug}', [ProductController::class, 'show'])->name('product');
