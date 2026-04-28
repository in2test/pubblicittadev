@props(['product'])

@php
    $generalMedia = $product->getMedia('images')->first(fn($m) => empty($m->custom_properties['color_ids'] ?? []));
    $firstMedia = $generalMedia ?? $product->getFirstMedia('images');
    $imageUrl = $firstMedia
        ? ($firstMedia->hasGeneratedConversion('medium') ? $firstMedia->getUrl('medium') : $firstMedia->getUrl())
        : 'https://placehold.co/600x800?text=' . urlencode($product->name);

    // Extract available colors from variations
    $availableColors = $product->variations->pluck('color')->unique('id')->filter()->sortBy('sort_order');

    $colorCount = $availableColors->count();
    $displayColors = $availableColors->take(8);
    $remainingColors = $colorCount - 8;
@endphp

<article
    class="group relative flex flex-col h-full border-b-4 border-transparent hover:border-primary transition-all duration-300">
    <a href="{{ route('product', ['category' => optional($product->category)->slug ?: 'uncategorized', 'slug' => $product->slug]) }}"
        class="flex flex-col h-full">
        <div class="absolute top-4 left-4 z-10 flex items-center gap-1">
            @if ($product->is_featured)
                <div class=" bg-vividauburn-800 text-white text-[10px] font-bold px-2 py-1 uppercase ">
                    Prodotto in Evidenza
                </div>
            @endif

            <div class="text-white text-[10px] font-bold px-2 py-1 uppercase  bg-gray-800">
                {{ optional($product->category)->slug ?: 'senza categoria' }}
            </div>
        </div>


        <div class=" aspect-4/5 overflow-hidden relative bg-white border border-gray-200 flex">
            <img class="m-auto object-cover grayscale-0 group-hover:grayscale transition-all duration-500 group-hover:scale-105  "
                src="{{ $imageUrl }}" alt="{{ $product->name }}" />
        </div>

        <div class="p-6 flex flex-col flex-1">
            <div class="flex justify-between items-start mb-2">
                <h3 class="text-lg font-bold leading-tight uppercase tracking-tight text-on-surface line-clamp-2">
                    {{ $product->name }}
                </h3>
                <div class="flex flex-col items-end">
                    @php
                        $discountedPrice = $product->getPriceForQuantity(1);
                        $basePrice = (float) $product->price;
                    @endphp
                    @if ($discountedPrice > 0 && $discountedPrice < $basePrice)
                        <span
                            class="font-mono text-sm font-bold text-primary">€{{ number_format((float) $discountedPrice, 2) }}</span>
                        <span
                            class="text-xs line-through text-gray-500 ml-2">€{{ number_format((float) $basePrice, 2) }}</span>
                    @elseif ($basePrice > 0)
                        <span
                            class="font-mono text-sm font-bold text-primary">€{{ number_format((float) $basePrice, 2) }}</span>
                    @else
                        <span
                            class="font-mono text-[10px] text-primary font-bold uppercase tracking-widest leading-none">Su
                            Richiesta</span>
                    @endif
                </div>
            </div>

            <code class="text-[10px] font-mono text-secondary mb-4">{{ $product->sku }}</code>

            <p class="text-sm text-on-surface line-clamp-2 mb-6 opacity-70">{{ $product->description }}</p>

            <!-- Color Preview Section -->
            @if ($colorCount > 0)
                <div class="mb-6 flex flex-wrap gap-1.5 items-center">
                    @foreach ($displayColors as $color)
                        @php $colorHex = $color->color_hex ?: '#ccc'; @endphp
                        <div class="w-3.5 h-3.5 rounded-full border border-outline-variant/30 shadow-sm"
                            style="background-color: {{ $colorHex }}" title="{{ $color->color_name }}"></div>
                    @endforeach

                    @if ($remainingColors > 0)
                        <span class="text-[10px] font-mono font-bold text-secondary ml-1">
                            +{{ $remainingColors }}
                        </span>
                    @endif
                </div>
            @endif

            <div class="mt-auto flex justify-between items-center pt-4 border-t border-outline-variant/10">
                <span
                    class="text-[10px] bg-secondary-container px-2 py-1 font-bold text-on-secondary-fixed-variant uppercase">
                    Premium Quality
                </span>
                <span
                    class="material-symbols-outlined text-primary hover:scale-110 transition-transform">add_circle</span>
            </div>
        </div>
    </a>
</article>
