<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\SyncNewWaveProductJob;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;

class AdminProductController extends Controller
{
    public function toggleActive(Product $product): RedirectResponse
    {
        $this->authorize('toggleActive', $product);

        $product->update(['is_active' => ! $product->is_active]);

        return redirect()->back();
    }

    public function sync(Product $product): RedirectResponse
    {
        $this->authorize('sync', $product);

        SyncNewWaveProductJob::dispatch($product->id);

        return redirect()->back();
    }
}
