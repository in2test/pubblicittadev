<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Jobs\CacheProductImagesJob;

class ImageCacheController extends Controller
{
    public function show(int $product_id)
    {
        $product = Product::findOrFail($product_id);
        return view('admin.cache-images', ['product' => $product]);
    }

    public function store(Request $request, int $product_id)
    {
        $product = Product::findOrFail($product_id);
        CacheProductImagesJob::dispatch($product);
        return redirect()->route('admin.cache.images', ['product' => $product_id])
            ->with('status', 'Cache initiated for product images');
    }
}
