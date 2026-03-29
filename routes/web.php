<?php

use App\Http\Controllers\HomePageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomePageController::class, 'index'])->name('home');
Route::view('/categories', 'categories')->name('categories');
Route::view('/product', 'product')->name('product');
Route::view('/services', 'services')->name('services');
Route::view('/contact', 'contact')->name('contact');
Route::view('/cart', 'cart')->name('cart');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
