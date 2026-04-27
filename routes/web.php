<?php

declare(strict_types=1);

use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuoteController;
use Illuminate\Support\Facades\Route;

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
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
});

require __DIR__.'/settings.php';

// dynamic routes for products and categories, these will be replaced with dynamic routes in the future
Route::get('/catalogo', [CategoryController::class, 'index'])->name('catalog');
Route::get('/catalogo/{category}', [CategoryController::class, 'show'])->name('category');
Route::get('/catalogo/{category}/{slug}', [ProductController::class, 'show'])->name('product');
Route::post('/quote', [QuoteController::class, 'store'])->name('quote.store');
