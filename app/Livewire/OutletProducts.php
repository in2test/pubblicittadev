<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class OutletProducts extends Component
{
    use WithPagination;

    public function render(): View
    {
        return view('livewire.outlet-products', [
            'products' => Product::with('category')
                ->hasOutletSkus()
                ->active()
                ->paginate(12),
        ])->layout('layouts.layout');
    }
}
