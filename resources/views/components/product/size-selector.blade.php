@props(['product'])

@php
$availableSizes = $product->variations
->pluck('size')
->unique('id')
->filter()
->sortBy('sort_order');
@endphp

@if ($availableSizes->count() > 0)
<!-- Size & Quantity Selection -->
<div class="space-y-4">
    <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">Taglie e Quantità</label>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($availableSizes as $size)
        <div x-show="!activeColorId || colorToSizes[activeColorId].includes({{ $size->id }})"
            class="flex items-center justify-between p-3 border border-outline-variant/20 bg-surface-container rounded transition-all">
            <span class="font-mono text-sm font-bold">{{ $size->size }}</span>


            <div>
                <input name="quantities[{{ $size->id }}]" 
                       type="number" 
                       min="0" 
                       x-model.number="quantities[{{ $size->id }}]"
                       placeholder="0"
                       class="w-32 h-12 rounded border border-outline-variant/20 bg-surface-container px-4 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
            </div>


        </div>
        @endforeach
    </div>

    <div class="mt-4 p-4 bg-primary/5 rounded border border-primary/20 flex justify-between items-center" x-show="totalQuantity > 0">
        <span class="text-xs font-mono uppercase tracking-widest text-primary">Totale Articoli</span>
        <span class="text-lg font-bold text-primary" x-text="totalQuantity"></span>
    </div>
</div>
@endif