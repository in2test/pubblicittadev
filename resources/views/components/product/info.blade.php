@props(['product'])

<div class="mb-2">
    <span class="font-mono text-[10px] tracking-tighter text-secondary bg-surface-container px-2 py-1">SKU:
        {{ $product->sku }}</span>
</div>
<h1 class="text-4xl lg:text-5xl font-black tracking-tighter text-on-surface mb-4 leading-none uppercase">
    {{ $product->name }}
</h1>
<div class="flex items-baseline gap-4 mb-8">
    <span class="text-3xl font-light text-primary tracking-tight"
        x-text="'€' + (basePrice + selectedPlacementPrice).toFixed(2)">€{{ number_format((float) $product->price, 2) }}</span>
    <span class="text-xs font-mono text-secondary">IVA INCLUSA</span>
</div>
<div class="mb-8 p-6 bg-surface-container-low border-l-4 border-primary">
    <p class="text-sm text-on-surface-variant leading-relaxed">
        {{ $product->description }}
    </p>
</div>
