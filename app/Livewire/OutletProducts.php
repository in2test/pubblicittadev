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
            'products' => Product::with([
                'category:id,name,slug',
                'productVariationTypes' => fn ($q) => $q->where('has_images', true)->select('id', 'product_id', 'variation_type_id', 'has_images'),
                'productVariationTypes.options:id,product_variation_type_id,variation_option_id,sort_order',
                'productVariationTypes.options.option:id,name,value,color_hex',
                'media' => fn ($query) => $query->orderBy('order_column')->limit(1),
                'images' => fn ($query) => $query->orderBy('order_by')->limit(1),
            ])
                ->hasOutletSkus()
                ->active()
                ->paginate(12),
        ])->layout('layouts.layout');
    }
}
