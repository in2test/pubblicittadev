@props(['product'])

@php
    /** @var \App\Models\Product $product */
    $imageUrl = $product->getFirstImageUrl('medium');
    $isAdmin = auth()->check() && auth()->user()->isAdmin();
    $adminEditUrl = $product->getAdminEditUrl();

    // Color Preview Data
    $colorData = $product->getPreviewColors(8);

    // Pricing Data
    $priceData = $product->getDisplayPriceData(1);
@endphp

<article
    class="group relative flex flex-col h-full border-b-4 border-transparent hover:border-vividauburn-700 transition-all duration-300 bg-gray-50">
    <a href="{{ route('product', ['category' => $product->category->slug ?? 'uncategorized', 'product' => $product->slug]) }}"
        class="flex flex-col h-full">

        {{-- Badges --}}
        <div class="absolute top-4 left-4 z-10 flex items-center gap-1">
            @if ($product->is_featured)
                <div class="bg-vividauburn-700 text-gray-100 text-[10px] font-bold px-2 py-1 uppercase tracking-widest">
                    Evidenza
                </div>
            @endif


        </div>

        {{-- Product Image --}}
        <div class="aspect-4/5 overflow-hidden relative bg-white border border-gray-100 flex">
            <img class="m-auto object-cover grayscale-60 group-hover:grayscale-0 transition-all duration-500 group-hover:scale-105"
                src="{{ $imageUrl }}" alt="{{ $product->name }}" loading="lazy" />
        </div>

        {{-- Product Info --}}
        <div class="p-6 flex flex-col flex-1">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-lg font-black leading-tight uppercase tracking-tight text-on-surface line-clamp-2">
                    {{ $product->name }}
                </h3>

                {{-- Pricing --}}
                <div class="flex flex-col items-end">
                    @if ($priceData['on_request'])
                        <span
                            class="font-mono text-[10px] text-primary font-bold uppercase tracking-widest leading-none">
                            Su Richiesta
                        </span>
                    @elseif ($priceData['is_discounted'])
                        <span class="font-mono text-sm font-bold text-primary">
                            €{{ number_format($priceData['price'], 2) }}
                        </span>
                        <span class="text-[10px] line-through text-gray-400 font-mono">
                            €{{ number_format($priceData['base_price'], 2) }}
                        </span>
                    @else
                        <span class="font-mono text-sm font-bold text-primary">
                            €{{ number_format($priceData['price'], 2) }}
                        </span>
                    @endif
                </div>
            </div>

            <code class="text-[10px] font-mono text-secondary mb-4 opacity-50">{{ $product->sku }}</code>

            <p class="text-xs text-on-surface line-clamp-2 mb-6 opacity-60 leading-relaxed">
                {{ $product->description }}
            </p>

            {{-- Color Preview --}}
            @if ($colorData['total'] > 0)
                <div class="mb-6 flex flex-wrap gap-1 items-center">
                    @foreach ($colorData['display'] as $color)
                        <div class="w-3 h-3 border border-gray-200" @style(['background-color: ' . ($color->color_hex ?: '#ccc')]) title="{{ $color->color_name }}">
                        </div>
                    @endforeach

                    @if ($colorData['remaining'] > 0)
                        <span class="text-[10px] font-mono font-bold text-secondary ml-1 opacity-50">
                            +{{ $colorData['remaining'] }}
                        </span>
                    @endif
                </div>
            @endif

            {{-- Footer / Admin Actions --}}
            <div class="mt-auto flex justify-between items-center pt-4 border-t border-gray-100">
                <span class="text-[10px] font-mono font-bold text-secondary uppercase tracking-widest">
                    {{ $product->category->name ?? 'Prodotti' }}
                </span>

                @if ($isAdmin)
                    <div class="flex items-center gap-2" onclick="event.preventDefault()">
                        <a href="{{ $adminEditUrl }}"
                            class="p-2 border border-gray-100 hover:bg-gray-100 transition-colors" target="_blank">
                            <span class="material-symbols-outlined text-sm text-gray-400">edit</span>
                        </a>
                        <form method="POST" action="{{ route('admin.products.toggle-active', $product) }}">
                            @csrf
                            <button type="submit"
                                class="border border-gray-100 px-3 py-2 text-[10px] font-bold uppercase tracking-widest {{ $product->is_active ? 'text-rose-600 hover:bg-rose-50' : 'text-emerald-600 hover:bg-emerald-50' }} transition-colors">
                                {{ $product->is_active ? 'Off' : 'On' }}
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </a>
</article>
