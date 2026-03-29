<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomePageController::class, 'index'])->name('home');
Route::get('/{category}/{slug}', [ProductController::class, 'index'])->name('product');
Route::get('/{category}', [CategoryController::class, 'index'])->name('category');
Route::view('/categories', 'categories')->name('categories');
Route::view('/services', 'services')->name('services');
Route::view('/contact', 'contact')->name('contact');
Route::view('/cart', 'cart')->name('cart');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
