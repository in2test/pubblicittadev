@props(['product'])

<div class="mb-2">
    <span class="font-mono text-[10px] tracking-tighter text-gray-800 bg-surface-container px-2 py-1">SKU:
        {{ $product->sku }}</span>
</div>
<h1 class="text-4xl lg:text-5xl font-black tracking-tighter text-gray-950    mb-4 leading-none uppercase">
    {{ $product->name }}
</h1>
<div class="flex items-baseline gap-4 mb-8">
    <template x-if="offerPrice > 0">
        <div class="flex items-baseline gap-3">
            <span class="text-3xl font-black text-vividauburn-600 tracking-tight"
                x-text="'€' + (offerPrice + selectedPlacementPrice).toFixed(2)">€{{ number_format((float) $product->offer_price, 2) }}</span>
            <span class="text-lg font-light text-gray-500 line-through tracking-tight"
                x-text="'€' + (basePrice + selectedPlacementPrice).toFixed(2)">€{{ number_format((float) $product->price, 2) }}</span>
        </div>
    </template>
    <template x-if="offerPrice <= 0">
        <span class="text-3xl font-light text-gray-900 tracking-tight"
            x-text="'€' + (basePrice + selectedPlacementPrice).toFixed(2)">€{{ number_format((float) $product->price, 2) }}</span>
    </template>
    <span class="text-xs font-mono text-gray-800">IVA INCLUSA</span>
</div>
<div class="mb-8 p-6 bg-surface-container-low border-l-4 border-vividauburn-600">
    <p class="text-sm text-gray-800 leading-relaxed">
        {{ $product->description }}
    </p>
</div>