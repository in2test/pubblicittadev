<x-layout>
    <!-- Header & Search Bar Section -->
    <section class="mb-12 mt-24 px-8 3xl:px-32">
        <div class="flex flex-col md:flex-row justify-between items-end gap-6 mb-8">
            <div>
                @if ($category->count() == 1)

                    <h1 class="text-4xl md:text-5xl font-black tracking-tighter text-on-background uppercase">
                        @if ($category->parent)
                            {{ $category->parent->name }}:
                        @endif
                        <span class="text-primary">{{ $category->name }}</span>
                    </h1>
                    <nav
                        class="py-4 flex items-center gap-2 mb-12 text-xs font-mono uppercase tracking-widest text-secondary">
                        <a class="hover:text-primary transition-colors" href="{{ route('home') }}">Home</a>
                        <span class="material-symbols-outlined text-[10px]">chevron_right</span>
                        @if ($category->parent)
                            <a class="hover:text-primary transition-colors"
                                href="{{ route('category', $category->parent->slug) }}">{{ $category->parent->name }}</a>
                            <span class="material-symbols-outlined text-[10px]">chevron_right</span>
                        @endif

                        <span class="text-on-surface font-bold">{{ $category->name }}</span>
                    </nav>
                @endisset
        </div>
        <div class="text-right">
            <span class="text-3xl font-light tracking-tighter text-on-surface">{{ $products->count() }}</span>
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
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 3xl:grid-cols-6 gap-8 px-8 3xl:px-32">
    @forelse ($products as $product)
        <x-product.card :$product />
    @empty
        <div class="col-span-full text-center py-16">
            <p class="text-xl text-secondary">Nessun prodotto trovato</p>
        </div>
    @endforelse

    @if ($products->hasPages())
    <div class="col-span-full mt-16">
        <nav class="flex items-center justify-center gap-2">
            @if ($products->onFirstPage())
                <span class="w-10 h-10 border border-slate-200 flex items-center justify-center text-slate-300">
                    <span class="material-symbols-outlined text-sm">chevron_left</span>
                </span>
            @else
                <a href="{{ $products->previousPageUrl() }}" 
                   class="w-10 h-10 border border-slate-200 flex items-center justify-center hover:bg-primary hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-sm">chevron_left</span>
                </a>
            @endif

            @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                @if ($page == $products->currentPage())
                    <span class="w-10 h-10 bg-primary text-white font-bold text-xs flex items-center justify-center">{{ str_pad($page, 2, '0', STR_PAD_LEFT) }}</span>
                @else
                    <a href="{{ $url }}" class="w-10 h-10 border border-slate-200 font-bold text-xs hover:bg-slate-50 flex items-center justify-center">{{ str_pad($page, 2, '0', STR_PAD_LEFT) }}</a>
                @endif
            @endforeach

            @if ($products->hasMorePages())
                <a href="{{ $products->nextPageUrl() }}" 
                   class="w-10 h-10 border border-slate-200 flex items-center justify-center hover:bg-primary hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                </a>
            @else
                <span class="w-10 h-10 border border-slate-200 flex items-center justify-center text-slate-300">
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                </span>
            @endif
        </nav>
    </div>
    @endif
</div>

</x-layout>
