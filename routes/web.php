<?php

declare(strict_types=1);

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// template routes for inspiration purposes only, these will be replaced with dynamic routes in the future

Route::view('/categories', 'categories')->name('categories');
Route::view('/services', 'services')->name('services');
Route::view('/contact', 'contact')->name('contact');
Route::view('/cart', 'cart')->name('cart');

Route::get('/', [HomePageController::class, 'index'])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

// dynamic routes for products and categories, these will be replaced with dynamic routes in the future
Route::get('/{category}/{slug}', [ProductController::class, 'show'])->name('product');
Route::get('/{category}', [CategoryController::class, 'show'])->name('category');
