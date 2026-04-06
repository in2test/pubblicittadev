@props(['products'])

<section class="py-24 bg-surface-container-low">
    <div class="px-8 3xl:px-32 mx-auto">
        <div class="flex justify-between items-center mb-16">
            <h2 class="text-3xl font-black uppercase tracking-tight">Prodotti in Evidenza</h2>
            <a class="text-primary  font-mono font-bold text-sm tracking-widest uppercase border-b-2 border-primary pb-1"
                href="{{ route('catalog') }}">Catalogo Completo</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3  2xl:grid-cols-4 3xl:grid-cols-6 gap-8">
            @foreach ($products as $product)
                @php
                    $imageUrl =
                        $product->getFirstMediaUrl('images', 'medium') ?:
                        'https://placehold.co/600x800?text=' . urlencode($product->name);
                @endphp



                <article
                    class="group relative bg-surface-container-lowest flex flex-col h-full border-b-4 border-transparent hover:border-primary transition-all duration-300">
                    <a href="{{ route('product', [$product->category->slug, $product->slug]) }}">
                        @if ($product->is_featured)
                            <div
                                class="absolute top-4 left-4 bg-primary text-white text-[10px] font-bold px-2 py-1 uppercase z-10">
                                Prodotto in Evidenza</div>
                        @endif
                        <div class=" aspect-5/8 overflow-hidden bg-surface-container relative">
                            <img class="w-full h-full object-cover grayscale-0 group-hover:grayscale transition-all duration-500 group-hover:scale-105"
                                data-alt="Premium navy corporate polo shirt layout" src="{{ $imageUrl }}" />
                        </div>
                        <div class="p-6 flex flex-col flex-1">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-lg font-bold leading-tight uppercase tracking-tight text-on-surface">
                                    {{ $product->name }}
                                </h3>
                                @if ($product->price > 0)
                                    <span class="font-mono text-xs">€{{ number_format($product->price, 2) }}</span>
                                @else
                                    <span class="font-mono text-xs text-primary font-bold">SU RICHIESTA</span>
                                @endif
                            </div>
                            <code class="text-[10px] font-mono text-secondary mb-4">Clique: {{ $product->sku }}</code>
                            <p class="text-sm text-on-surface line-clamp-2 mb-6">{{ $product->description }}</p>
                            <div class="mt-auto flex justify-between items-center">
                                <span
                                    class="text-[10px] bg-secondary-container px-2 py-1 font-bold text-on-secondary-fixed-variant uppercase">Oeko-Tex
                                    100</span>
                                <button
                                    class="material-symbols-outlined text-primary hover:scale-110 transition-transform">add_circle</button>
                            </div>
                        </div>
                    </a>
                </article>
            @endforeach
        </div>
    </div>
</section>
