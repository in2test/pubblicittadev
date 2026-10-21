<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SyncLevel;
use App\Jobs\SyncNewWaveProductJob;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminProductController extends Controller
{
    public function toggleActive(Product $product): RedirectResponse
    {
        $this->authorize('toggleActive', $product);

        $product->update(['is_active' => ! $product->is_active]);

        return redirect()->back();
    }

    public function sync(Product $product, Request $request): RedirectResponse
    {
        $this->authorize('sync', $product);

        $validated = $request->validate([
            'level' => ['nullable', 'string', Rule::enum(SyncLevel::class)],
        ]);

        $level = SyncLevel::tryFrom($validated['level'] ?? '') ?? SyncLevel::Full;

        SyncNewWaveProductJob::dispatch($product->id, $level);

        return redirect()->back();
    }
}
