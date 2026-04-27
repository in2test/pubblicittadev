@props(['product'])

<div class="mb-2">
    <span class="font-mono text-[10px] tracking-tighter text-gray-800 bg-surface-container px-2 py-1">SKU:
        {{ $product->sku }}</span>
</div>
<h1 class="text-4xl lg:text-5xl font-black tracking-tighter text-gray-950    mb-4 leading-none uppercase">
    {{ $product->name }}
</h1>
@php
$disc = $product->getPriceForQuantity(1);
$base = (float) $product->price;
$offer = (float) ($product->offer_price ?? 0);
@endphp
<div class="flex items-baseline gap-4 mb-8">
    @if ($disc > 0 && $disc < $base)
        <span class="text-3xl font-black text-vividauburn-600">€{{ number_format((float) $disc, 2) }}</span>
        <span class="text-lg font-light text-gray-500 line-through tracking-tight">€{{ number_format((float) $base, 2) }}</span>
    @elseif ($offer > 0)
        <span class="text-3xl font-black text-vividauburn-600">€{{ number_format((float) $offer, 2) }}</span>
        <span class="text-lg font-light text-gray-500 line-through tracking-tight">€{{ number_format((float) $base, 2) }}</span>
    @else
        <span class="text-3xl font-light text-gray-900 tracking-tight">€{{ number_format((float) $base, 2) }}</span>
    @endif
    <span class="text-xs font-mono text-gray-800">IVA INCLUSA</span>
</div>
<div class="mb-8 p-6 bg-surface-container-low border-l-4 border-vividauburn-600">
    <p class="text-sm text-gray-800 leading-relaxed">
        {{ $product->description }}
    </p>
</div>
