<?php

use Livewire\Component;

new class extends Component {
    //
    public $product;
    public $category;
    public $colorId;
    public $jobId;
    public $images;

    public function mount($product, $category, $colorId, $jobId): void
    {
        $this->product = $product;
        $this->category = $product?->category ?? $category;
        $this->colorId = $colorId ?? null;
        $this->jobId = $jobId ?? null;
        $this->images = $product->images->where('color_id', '=', $colorId)->values() ?? [];
    }
};
?>

<div>
    <x-product.breadcrumbs :$product :$category />
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 px-8 py-12 3xl:px-32 bg-gray-200 text-gray-900">
        <div class="lg:col-span-7 2xl:col-span-5 ">
            <x-product.gallery :images="$images" />
        </div>
        <!-- Right Column: Info & Config -->
        <div class="lg:col-span-5 2xl:col-span-7 flex flex-col">
            <x-product.info :$product />

            <!-- Quantity Discounts List -->
            @if ($product->getQuantityDiscounts()->isNotEmpty())
                <div class="mt-6 p-4 bg-primary/5 border border-primary/20 rounded-xl">
                    <div class="flex items-center gap-2 text-primary font-semibold mb-3">
                        <span class="material-symbols-outlined text-lg">local_offer</span>
                        <h3 class="text-sm uppercase tracking-wider">Sconti per quantità</h3>
                    </div>
                    <ul class="space-y-2">
                        @foreach ($product->getQuantityDiscounts() as $discount)
                            <li
                                class="flex justify-between text-sm text-gray-600 border-b border-primary/10 pb-2 last:border-0 last:pb-0">
                                <span>{{ $discount->description ?: "Da {$discount->min_quantity} pezzi" }}</span>
                                <span
                                    class="font-medium text-primary">-{{ number_format($discount->discount_value * 100, 0) }}%</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-product.quote-form :$product :$colorId />

            <x-product.trust-badges />
        </div>
    </div>
    <x-product.technical-specs />
</div>
