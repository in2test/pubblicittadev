@props(['product'])

@php
    /** @var \App\Models\Product $product */
    $priceData = $product->getDisplayPriceData(1);
@endphp

@php
    $isAdmin = auth()->check() && auth()->user()->isAdmin();
    $adminEditUrl = $product->getAdminEditUrl();
@endphp

<div class="mb-2">
    <span class="font-mono text-[10px] tracking-tighter text-gray-800 bg-surface-container px-2 py-1">
        SKU: {{ $product->sku }}
    </span>
</div>

<h1 class="text-4xl lg:text-5xl font-black tracking-tighter text-gray-950 mb-4 leading-none uppercase">
    {{ $product->name }}
</h1>
@if ($isAdmin)
    <div class=" mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-2">
            <span
                class="inline-flex items-center rounded-full bg-white/90 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-slate-700 shadow-sm border border-slate-200">
                {{ $product->is_active ? 'Prodotto attivo' : 'Prodotto non attivo' }}
            </span>
            <a href="{{ $adminEditUrl }}"
                class="inline-flex items-center rounded-full bg-primary px-4 py-2 text-xs font-bold uppercase tracking-widest text-white hover:bg-primary-700 transition-colors">
                Modifica prodotto
            </a>
            <form method="POST" action="{{ route('admin.products.toggle-active', $product) }}" class="inline">
                @csrf
                <button type="submit"
                    class="inline-flex items-center rounded-full {{ $product->is_active ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700' }} px-4 py-2 text-xs font-bold uppercase tracking-widest text-white transition-colors">
                    {{ $product->is_active ? 'Disattiva prodotto' : 'Attiva prodotto' }}
                </button>
            </form>
            <form method="POST" action="{{ route('admin.products.sync', $product) }}" class="inline">
                @csrf
                <button type="submit"
                    class="inline-flex items-center rounded-full bg-sky-600 px-4 py-2 text-xs font-bold uppercase tracking-widest text-white hover:bg-sky-700 transition-colors">
                    Sincronizza prodotto
                </button>
            </form>
        </div>
    </div>
@endif
<div class="flex items-baseline gap-4 mb-8">
    @if ($priceData['on_request'])
        <span class="text-3xl font-black text-primary uppercase">Su Richiesta</span>
    @elseif ($priceData['is_discounted'])
        <span class="text-3xl font-black text-primary">€{{ number_format($priceData['price'], 2) }}</span>
        <span
            class="text-lg font-light text-gray-500 line-through tracking-tight">€{{ number_format($priceData['base_price'], 2) }}</span>
    @else
        <span
            class="text-3xl font-light text-gray-900 tracking-tight">€{{ number_format($priceData['base_price'], 2) }}</span>
    @endif

    <span class="text-xs font-mono text-gray-800">IVA INCLUSA</span>
</div>

<div class="mb-8 p-6 bg-surface-container-low border-l-4 border-primary">
    <p class="text-sm text-gray-800 leading-relaxed">
        {{ $product->description }}
    </p>
</div>
