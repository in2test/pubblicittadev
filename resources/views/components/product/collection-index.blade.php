@props(['collections'])

@php
    /**
     * @var \Illuminate\Support\Collection<int, \App\Models\Collection> $collections
     */
    $isSingleCollection = $collections->count() === 1;
@endphp

@if ($collections->isNotEmpty())
    <section class="mt-16 px-8 py-16 3xl:px-32 bg-gray-50 border-t-4 border-gray-950">
        <div class="max-w-7xl mx-auto">
            <div class="mb-12">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-gray-200 text-gray-800 text-[11px] font-mono uppercase tracking-widest rounded mb-3">
                    <span class="material-symbols-outlined text-sm">inventory_2</span>
                    {{ $isSingleCollection ? $collections->first()->name : 'Collezioni & Kit' }}
                </div>
                <h2 class="text-3xl lg:text-4xl font-black tracking-tight uppercase text-gray-950">
                    {{ $isSingleCollection ? 'Prodotti Della Stessa Collezione' : 'Prodotti Delle Tue Collezioni' }}
                </h2>
                <div class="h-1 w-20 bg-primary mt-2"></div>
            </div>

            @foreach ($collections as $collection)
                <div class="mb-14 last:mb-0">
                    @unless ($isSingleCollection)
                        <div class="flex flex-wrap items-baseline justify-between gap-3 mb-6 border-b border-gray-200 pb-3">
                            <h3 class="text-xl font-black uppercase tracking-tight text-gray-950">
                                {{ $collection->name }}
                            </h3>
                        </div>
                    @endunless

                    <x-product.grid :products="$collection->products" emptyMessage="Nessun altro prodotto in questa collezione." />

                    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                        @if (($collection->siblings_count ?? 0) > count($collection->products))
                            <span class="text-[10px] font-mono uppercase tracking-widest text-gray-500">
                                {{ count($collection->products) }} di {{ $collection->siblings_count }} articoli
                            </span>
                        @else
                            <span></span>
                        @endif

                        <a href="{{ route('collections.show', $collection->slug) }}"
                           class="inline-flex items-center gap-2 text-xs font-mono font-bold uppercase tracking-widest text-gray-600 hover:text-gray-950 transition-colors">
                            Vedi Tutti gli Articoli della Collezione
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
