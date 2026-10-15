<x-layout>
    <x-slot:title>
        {{ $collection->name }} | Bundle & Kit PubbliCittà24
    </x-slot:title>

    <x-slot:description>
        {{ !empty($collection->description) ? Str::limit(trim(strip_tags($collection->description)), 150) : 'Scopri il kit ' . $collection->name . ' personalizzabile con stampa professionale e preventivo immediato online.' }}
    </x-slot:description>

    <x-slot:canonical>
        {{ route('collections.show', $collection->slug) }}
    </x-slot:canonical>

    @php
        $bannerUrl = $collection->getBannerUrl('large') ?? ($collection->products->first()?->getFirstImageUrl('large'));
    @endphp

    {{-- Collection Header / Hero Banner --}}
    <section class="relative bg-gray-950 text-gray-50 border-b-2 border-gray-950 overflow-hidden">
        @if($bannerUrl)
            <div class="absolute inset-0 z-0">
                <img src="{{ $bannerUrl }}" alt="{{ $collection->name }}" class="w-full h-full object-cover opacity-25">
                <div class="absolute inset-0 bg-linear-to-r from-gray-50 via-gray-50/90 to-gray-50/60"></div>
            </div>
        @endif

        <div class="relative z-10 px-6 lg:px-12 3xl:px-24 py-16 md:py-24 max-w-5xl">
            <nav class="pb-6 flex items-center gap-3 text-[10px] font-mono uppercase tracking-[0.3em] text-gray-400">
                <a class="hover:text-accent-500 transition-colors" href="{{ route('home') }}">Home</a>
                <span class="text-gray-600">/</span>
                <a class="hover:text-accent-500 transition-colors" href="{{ route('collections.index') }}">Collezioni</a>
                <span class="text-gray-600">/</span>
                <span class="text-gray-950 font-bold truncate">{{ $collection->name }}</span>
            </nav>

            <div class="flex flex-wrap items-center gap-3 mb-4">
                <span class="bg-accent-500 text-gray-950 text-xs font-black uppercase tracking-widest px-3 py-1">
                    KIT & BUNDLE
                </span>
                <span class="bg-gray-800 text-gray-200 border border-gray-700 text-xs font-mono uppercase px-3 py-1">
                    {{ $collection->products->count() }} {{ $collection->products->count() === 1 ? 'Prodotto Incluso' : 'Prodotti Inclusi' }}
                </span>
            </div>

            <h1 class="text-4xl sm:text-6xl md:text-7xl font-black uppercase tracking-tighter text-gray-950 mb-6 leading-[0.95]">
                {{ $collection->name }}
            </h1>

            @if($collection->description)
                <p class="text-base sm:text-lg text-gray-700 font-sans leading-relaxed max-w-3xl mb-8">
                    {{ $collection->description }}
                </p>
            @endif

            <div class="flex flex-wrap items-center gap-4 text-xs font-mono">
                <a href="#prodotti" class="bg-accent-500 hover:bg-accent-600 text-gray-950 font-bold px-6 py-3.5 uppercase tracking-widest transition-colors inline-flex items-center gap-2">
                    Visualizza Prodotti nel Bundle
                    <span class="material-symbols-outlined text-sm">arrow_downward</span>
                </a>
                <a href="https://wa.me/390775520273?text={{ urlencode('Salve, vorrei informazioni sul kit: ' . $collection->name) }}" target="_blank"
                   class="bg-gray-900 hover:bg-gray-800 border border-gray-700 text-gray-100 px-6 py-3.5 uppercase tracking-widest transition-colors inline-flex items-center gap-2">
                    Richiedi Kit Personalizzato su WhatsApp
                </a>
            </div>
        </div>
    </section>

    {{-- Informational Kit Banner --}}
    <section class="bg-surface-container-low border-b border-gray-200 py-6 px-6 lg:px-12 3xl:px-24">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-xs font-mono text-gray-700">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-accent-600 text-xl">tune</span>
                <span>Puoi configurare taglie, colori e opzioni di stampa singolarmente per ciascun articolo del kit.</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-accent-600 text-xl">verified</span>
                <span>File di grafica controllato gratuitamente prima della produzione.</span>
            </div>
        </div>
    </section>

    {{-- Product Grid in Bundle --}}
    <section id="prodotti" class="py-16 md:py-24 px-6 lg:px-12 3xl:px-24">
        <div class="flex justify-between items-end mb-12">
            <div>
                <span class="font-mono text-xs text-accent-600 font-bold uppercase tracking-[0.2em] block mb-2">Composizione</span>
                <h2 class="text-2xl md:text-4xl font-black uppercase tracking-tight text-gray-950">
                    Articoli Inclusi nella Collezione
                </h2>
            </div>
            <a href="{{ route('catalog') }}" class="hidden sm:inline-flex text-xs font-mono font-bold uppercase tracking-widest text-gray-600 hover:text-gray-950 transition-colors items-center gap-1">
                Tutto il Catalogo &rarr;
            </a>
        </div>

        <x-product.grid :products="$collection->products" emptyMessage="Nessun prodotto associato a questo bundle al momento." />
    </section>
</x-layout>
