<x-layout>
    <x-slot:title>
        Collezioni & Kit Completi | PubbliCittà24
    </x-slot:title>

    <x-slot:description>
        Scopri le nostre collezioni e bundle tematici: kit apertura attività, abbigliamento sportivo e kit da lavoro personalizzati con stampe di alta qualità.
    </x-slot:description>

    <x-slot:canonical>
        {{ route('collections.index') }}
    </x-slot:canonical>

    <section class="mt-12 mb-8 px-6 lg:px-12 3xl:px-24">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 pb-8 border-b-2 border-gray-950">
            <div>
                <nav class="pb-3 flex items-center gap-3 text-[10px] font-mono uppercase tracking-[0.3em] text-secondary">
                    <a class="hover:text-primary transition-colors" href="{{ route('home') }}">Home</a>
                    <span class="text-gray-300">/</span>
                    <span class="text-on-surface font-bold">Collezioni & Kit</span>
                </nav>
                <h1 class="text-4xl sm:text-5xl md:text-7xl font-black tracking-tighter text-on-background uppercase break-words">
                    <span class="text-primary">Collezioni</span> & Kit
                </h1>
                <p class="mt-4 text-gray-600 font-sans max-w-2xl text-base leading-relaxed">
                    Soluzioni coordinate studiate per offrirti tutto ciò di cui hai bisogno in un unico pacchetto: dall'apertura di un locale ai kit tecnici per eventi e sport.
                </p>
            </div>
            <div class="font-mono text-xs text-gray-500 uppercase tracking-widest">
                {{ $collections->total() }} {{ $collections->total() === 1 ? 'Bundle disponibile' : 'Bundle disponibili' }}
            </div>
        </div>
    </section>

    <section class="py-12 px-6 lg:px-12 3xl:px-24">
        @if($collections->isEmpty())
            <div class="py-32 text-center bg-gray-50 border-2 border-gray-950">
                <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">inventory_2</span>
                <h2 class="text-2xl font-black uppercase tracking-tight text-gray-950">Nessuna collezione disponibile al momento</h2>
                <p class="text-gray-500 font-mono text-sm mt-2">Stiamo preparando nuovi kit e offerte bundle. Torna a trovarci presto!</p>
                <a href="{{ route('catalog') }}" class="inline-block mt-8 bg-gray-950 text-gray-50 px-8 py-3.5 text-xs font-mono font-bold uppercase tracking-widest hover:bg-accent-600 transition-colors">
                    Sfoglia il Catalogo Completo
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($collections as $collection)
                    @php
                        $bannerUrl = $collection->getBannerUrl('large') 
                            ?? ($collection->products->first()?->getFirstImageUrl('large'));
                    @endphp
                    <article class="group bg-white border-2 border-gray-950 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] hover:shadow-[10px_10px_0px_0px_rgba(0,0,0,1)] hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between overflow-hidden">
                        <div>
                            <div class="aspect-16/9 bg-gray-950 relative overflow-hidden border-b-2 border-gray-950">
                                @if($bannerUrl)
                                    <img src="{{ $bannerUrl }}" 
                                         alt="{{ $collection->name }}" 
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                         loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-600">
                                        <span class="material-symbols-outlined text-4xl">style</span>
                                    </div>
                                @endif
                                <div class="absolute inset-0 bg-linear-to-t from-gray-950/80 via-transparent to-transparent"></div>
                                <div class="absolute top-3 left-3 flex gap-2">
                                    @if($collection->is_featured)
                                        <span class="bg-accent-500 text-gray-950 text-[10px] font-black uppercase tracking-widest px-2.5 py-1">
                                            Evidenza
                                        </span>
                                    @endif
                                    <span class="bg-gray-950 text-gray-50 border border-gray-700 text-[10px] font-mono uppercase px-2 py-1">
                                        {{ $collection->products_count }} Prodotti
                                    </span>
                                </div>
                            </div>

                            <div class="p-6">
                                <h2 class="text-xl font-black uppercase tracking-tight text-gray-950 mb-3 group-hover:text-accent-600 transition-colors">
                                    {{ $collection->name }}
                                </h2>
                                @if($collection->description)
                                    <p class="text-gray-600 text-sm leading-relaxed mb-4">
                                        {{ Str::limit(strip_tags($collection->description), 120) }}
                                    </p>
                                @endif

                                @if($collection->products->isNotEmpty())
                                    <div class="pt-3 border-t border-gray-100">
                                        <ul class="space-y-1">
                                            @foreach($collection->products->take(3) as $product)
                                                <li class="text-xs text-gray-700 flex items-center gap-1.5 truncate">
                                                    <span class="material-symbols-outlined text-accent-500 text-xs">arrow_right</span>
                                                    <span class="truncate">{{ $product->name }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="p-6 pt-0">
                            <a href="{{ route('collections.show', $collection->slug) }}"
                               class="w-full bg-gray-950 group-hover:bg-accent-600 text-gray-50 py-3 px-4 font-mono text-xs font-bold uppercase tracking-widest transition-colors flex items-center justify-center gap-2">
                                Vedi Kit & Prodotti
                                <span class="material-symbols-outlined text-sm">arrow_forward</span>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-12">
                {{ $collections->links() }}
            </div>
        @endif
    </section>
</x-layout>
