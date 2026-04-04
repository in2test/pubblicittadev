<x-layout>
    <!-- Header & Search Bar Section -->
    <section class="mb-12 mt-24 px-8 3xl:px-32">
        <div class="flex flex-col md:flex-row justify-between items-end gap-6 mb-8">
            <div>
                @if($category->count() == 1)

                <h1 class="text-4xl md:text-5xl font-black tracking-tighter text-on-background uppercase">
                    @if($category->parent)
                        {{ $category->parent->name }}:
                    @endif
                    <span class="text-primary">{{ $category->name }}</span>
                </h1>
                <nav
                    class="py-4 flex items-center gap-2 mb-12 text-xs font-mono uppercase tracking-widest text-secondary">
                    <a class="hover:text-primary transition-colors" href="{{ route('home') }}">Home</a>
                    <span class="material-symbols-outlined text-[10px]">chevron_right</span>
                    @if($category->parent)
                        <a class="hover:text-primary transition-colors"
                            href="{{ route('category', $category->parent->slug) }}">{{ $category->parent->name }}</a>
                        <span class="material-symbols-outlined text-[10px]">chevron_right</span>
                    @endif

                    <span class="text-on-surface font-bold">{{ $category->name }}</span>
                </nav>
                @endisset
            </div>
            <div class="text-right">
                <span class="text-3xl font-light tracking-tighter text-on-surface">48</span>
                <span class="text-xs uppercase tracking-widest text-secondary block">Prodotti trovati</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-1 shadow-sm flex items-center gap-2 border-b-2 border-primary">
            <span class="material-symbols-outlined px-3 text-secondary">search</span>
            <input class="flex-1 border-none focus:ring-0 text-sm bg-transparent py-4 font-body"
                placeholder="Filtra per nome prodotto, SKU o specifica tecnica..." type="text" />
            <button
                class="bg-surface-container hover:bg-surface-container-high px-6 py-3 text-xs font-bold uppercase tracking-widest transition-colors mr-1">Ordina
                per: Rilevanza</button>
        </div>
    </section>
    <!-- Product Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-8 px-8 3xl:px-32">
        <!-- Product Card 1 -->
        @foreach ($products as $product)
            <article
                class="group bg-surface-container-lowest flex flex-col h-full border-b-4 border-transparent hover:border-primary transition-all duration-300">
                <a href="{{ route('product', [$product->category->slug, $product->slug]) }}">
                    @php
                        $imageUrl = $product->images->first()?->image_url ?? ($product->images->first()?->image_path ? asset('storage/' . $product->images->first()?->image_path) : 'https://placehold.co/600x800?text=' . urlencode($product->name));
                    @endphp
                    <div class="aspect-[4/5] overflow-hidden bg-surface-container relative">
                        <img class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-500 group-hover:scale-105"
                            data-alt="Technical high-visibility safety jacket industrial worker" src="{{ $imageUrl }}" />
                        @if($product->is_featured)
                            <div class="absolute top-4 right-4">
                                <span
                                    class="bg-primary text-white text-[10px] font-bold px-2 py-1 uppercase tracking-tighter">Prodotto
                                    in Evidenza</span>
                            </div>
                        @endif
                    </div>
                    <div class="p-6 flex flex-col flex-1">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-lg font-bold leading-tight uppercase tracking-tight text-on-surface">
                                {{ $product->name }}
                            </h3>
                            <span class="text-primary font-black text-lg">{{ $product->price }}</span>
                        </div>
                        <code class="text-[10px] font-mono text-secondary mb-4">SKU: {{ $product->sku }}</code>
                        <p class="text-sm text-secondary-container line-clamp-2 mb-6">{{ $product->description }}</p>
                        <div class="mt-auto flex justify-between items-center">
                            <span
                                class="text-[10px] bg-secondary-container px-2 py-1 font-bold text-on-secondary-fixed-variant uppercase">EN
                                ISO 20471</span>
                            <button
                                class="material-symbols-outlined text-primary hover:scale-110 transition-transform">add_circle</button>
                        </div>
                    </div>
                </a>
            </article>
        @endforeach
        <!-- Product Card 2 -->

        <!-- Pagination (Architectural style) -->
        <div class="mt-16 flex items-center justify-center gap-2">
            <button
                class="w-10 h-10 border border-slate-200 flex items-center justify-center hover:bg-primary hover:text-white transition-colors">
                <span class="material-symbols-outlined text-sm">chevron_left</span>
            </button>
            <button class="w-10 h-10 bg-primary text-white font-bold text-xs">01</button>
            <button class="w-10 h-10 border border-slate-200 font-bold text-xs hover:bg-slate-50">02</button>
            <button class="w-10 h-10 border border-slate-200 font-bold text-xs hover:bg-slate-50">03</button>
            <span class="px-2 text-secondary">...</span>
            <button class="w-10 h-10 border border-slate-200 font-bold text-xs hover:bg-slate-50">12</button>
            <button
                class="w-10 h-10 border border-slate-200 flex items-center justify-center hover:bg-primary hover:text-white transition-colors">
                <span class="material-symbols-outlined text-sm">chevron_right</span>
            </button>
        </div>
    </div>

</x-layout>