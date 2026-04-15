@props(['product'])

@php
    $imageUrl = $product->getFirstMediaUrl('images', 'medium') ?: 'https://placehold.co/600x800?text=' . urlencode($product->name);
    
    // Extract available colors from variations
    $availableColors = $product->variations
        ->pluck('color')
        ->unique('id')
        ->filter()
        ->sortBy('sort_order');
    
    $colorCount = $availableColors->count();
    $displayColors = $availableColors->take(8);
    $remainingColors = $colorCount - 8;
@endphp

<article class="group relative bg-surface-container-lowest flex flex-col h-full border-b-4 border-transparent hover:border-primary transition-all duration-300">
    <a href="{{ route('product', ['category'=>$product->category->slug?:'category', 'slug' => $product->slug]) }}" class="flex flex-col h-full">
        @if ($product->is_featured)
            <div class="absolute top-4 left-4 bg-primary text-white text-[10px] font-bold px-2 py-1 uppercase z-10">
                Prodotto in Evidenza
            </div>
            @else
            <div class="absolute top-4 left-4 bg-primary text-white text-[10px] font-bold px-2 py-1 uppercase z-10">
                {{ $product->category->slug }}
            </div>
        @endif
        
        <div class="aspect-4/5 overflow-hidden bg-surface-container relative">
            <img class="w-full h-full object-cover grayscale-0 group-hover:grayscale transition-all duration-500 group-hover:scale-105"
                src="{{ $imageUrl }}" 
                alt="{{ $product->name }}" />
        </div>
        
        <div class="p-6 flex flex-col flex-1">
            <div class="flex justify-between items-start mb-2">
                <h3 class="text-lg font-bold leading-tight uppercase tracking-tight text-on-surface line-clamp-2">
                    {{ $product->name }}
                </h3>
                <div class="flex flex-col items-end">
                    @if ($product->price > 0)
                        <span class="font-mono text-sm font-bold text-primary">€{{ number_format((float)$product->price, 2) }}</span>
                    @else
                        <span class="font-mono text-[10px] text-primary font-bold uppercase tracking-widest leading-none">Su Richiesta</span>
                    @endif
                </div>
            </div>
            
            <code class="text-[10px] font-mono text-secondary mb-4">{{ $product->sku }}</code>
            
            <p class="text-sm text-on-surface line-clamp-2 mb-6 opacity-70">{{ $product->description }}</p>
            
            <!-- Color Preview Section -->
            @if ($colorCount > 0)
                <div class="mb-6 flex flex-wrap gap-1.5 items-center">
                    @foreach ($displayColors as $color)
                        <div class="w-3.5 h-3.5 rounded-full border border-outline-variant/30 shadow-sm"
                             style="background-color: {{ $color->color_hex ?: '#ccc' }}"
                             title="{{ $color->color_name }}"></div>
                    @endforeach
                    
                    @if ($remainingColors > 0)
                        <span class="text-[10px] font-mono font-bold text-secondary ml-1">
                            +{{ $remainingColors }}
                        </span>
                    @endif
                </div>
            @endif
            
            <div class="mt-auto flex justify-between items-center pt-4 border-t border-outline-variant/10">
                <span class="text-[10px] bg-secondary-container px-2 py-1 font-bold text-on-secondary-fixed-variant uppercase">
                    Premium Quality
                </span>
                <span class="material-symbols-outlined text-primary hover:scale-110 transition-transform">add_circle</span>
            </div>
        </div>
    </a>
</article>
