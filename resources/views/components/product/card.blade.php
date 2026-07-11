@props(['product', 'index' => 0])

@php
    /** @var \App\Models\Product $product */
    $imageUrl = $product->getFirstImageUrl('medium');
    // Color Preview Data
    $colorData = $product->getPreviewColors(8);

    // Pricing Data
    $startingPrice = $product->getStartingPrice();
@endphp

<article
    class="group relative flex flex-col h-full border-b-4 border-transparent hover:border-accent-700 transition-all duration-300 bg-gray-50">
    
    <a href="{{ route('product', ['category' => $product->category->slug ?? 'uncategorized', 'product' => $product->slug]) }}"
        class="absolute inset-0 z-10" aria-label="{{ $product->name }}"></a>
        
    <div class="flex flex-col h-full relative z-0 pointer-events-none">

        {{-- Badges --}}
        <div class="absolute top-4 left-4 z-10 flex items-center gap-1">
            @if ($product->is_featured)
                <div class="bg-accent-700 text-gray-100 text-[10px] font-bold px-2 py-1 uppercase tracking-widest">
                    Evidenza
                </div>
            @endif


        </div>

        {{-- Product Image --}}
        <div class="aspect-5/6 relative bg-white border border-gray-100 overflow-hidden">
            <img class="w-full h-full object-contain grayscale-40 group-hover:grayscale-0 transition-all duration-400 ease-in-out group-hover:scale-90"
                src="{{ $imageUrl }}" alt="{{ $product->name }}" width="350" height="420"
                @if($index === 0) loading="eager" fetchpriority="high" @else loading="lazy" @endif />
        </div>

        {{-- Product Info --}}
        <div class="p-6 flex flex-col flex-1">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-lg font-black leading-tight uppercase tracking-tight text-on-surface line-clamp-2">
                    {{ $product->name }}
                </h3>

                {{-- Pricing --}}
                <div class="flex flex-col items-end">
                    @if ($product->isOnRequest())
                        <span class="font-mono text-[10px] text-primary font-bold uppercase tracking-widest leading-none">
                            Su Richiesta
                        </span>
                    @else
                        <span class="text-[10px] text-gray-500 uppercase tracking-wider font-bold mb-0.5">A partire da</span>
                        <div class="flex items-baseline gap-1">
                            <span class="font-mono text-sm font-bold text-primary">
                                @if ($product->pricing_model === 'area')
                                    €{{ number_format($product->getStartingUnitPrice(), 2, ',', '.') }}<span class="text-[10px] font-bold"> / mq</span>
                                @else
                                    €{{ number_format($startingPrice, 2, ',', '.') }}
                                @endif
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <code class="text-[10px] font-mono text-gray-700 mb-4">{{ $product->sku }}</code>

            <p class="text-xs text-gray-700 line-clamp-2 mb-6 leading-relaxed">
                {{ $product->description }}
            </p>

            {{-- Color Preview --}}
            @if ($colorData['total'] > 0)
                <div class="mb-6 flex flex-wrap gap-1 items-center">
                    @foreach ($colorData['display'] as $color)
                        @php
                            $hexColors = $color->getHexColors();
                            $swatchStyle = count($hexColors) >= 2
                                ? 'background: linear-gradient(135deg, ' . $hexColors[0] . ' 50%, ' . $hexColors[1] . ' 50%)'
                                : 'background-color: ' . $hexColors[0];
                        @endphp
                        <div class="w-3 h-3 border border-gray-200 shadow-sm" @style([$swatchStyle]) title="{{ $color->name }}">
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
            <div class="mt-auto flex justify-between items-center pt-4 border-t border-gray-100 relative z-20 pointer-events-auto">
                <span class="text-[10px] font-mono font-bold text-secondary uppercase tracking-widest">
                    {{ $product->category->name ?? 'Prodotti' }}
                </span>


            </div>
        </div>
    </div>
</article>