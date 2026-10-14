@props(['collections'])

@if($collections->isNotEmpty())
    <section class="py-20 bg-gray-100 border-t-2 border-b-2 border-gray-950">
        <div class="px-8 3xl:px-32 mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-14 gap-6">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <span class="font-mono text-xs font-bold uppercase tracking-[0.3em] text-accent-600">Kit Completi & Soluzioni Chiavi in Mano</span>
                        <div class="h-px w-10 bg-accent-600/40"></div>
                    </div>
                    <h2 class="text-3xl md:text-5xl font-black uppercase tracking-tight text-gray-950">
                        Collezioni & Bundle in Evidenza
                    </h2>
                </div>
                <a class="text-gray-950 hover:text-accent-600 font-mono font-bold text-xs tracking-widest uppercase border-b-2 border-gray-950 hover:border-accent-600 pb-1 transition-colors flex items-center gap-2"
                   href="{{ route('collections.index') }}">
                    Tutte le Collezioni
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                @foreach ($collections as $collection)
                    @php
                        $bannerUrl = $collection->getBannerUrl('large') 
                            ?? ($collection->products->first()?->getFirstImageUrl('large'));
                    @endphp
                    <article class="group relative bg-white border-2 border-gray-950 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] hover:shadow-[10px_10px_0px_0px_rgba(0,0,0,1)] hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between overflow-hidden">
                        <div>
                            {{-- Banner Visual --}}
                            <div class="relative aspect-21/9 bg-gray-950 overflow-hidden border-b-2 border-gray-950">
                                @if($bannerUrl)
                                    <img src="{{ $bannerUrl }}" 
                                         alt="{{ $collection->name }}" 
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 opacity-90 group-hover:opacity-100"
                                         loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gray-900 text-gray-600">
                                        <span class="material-symbols-outlined text-5xl">auto_awesome_motion</span>
                                    </div>
                                @endif
                                <div class="absolute inset-0 bg-linear-to-t from-gray-950/80 via-transparent to-transparent"></div>
                                <div class="absolute top-4 left-4 flex gap-2">
                                    <span class="bg-accent-500 text-gray-950 text-[10px] font-black uppercase tracking-widest px-3 py-1">
                                        BUNDLE SPECIAL
                                    </span>
                                    @if($collection->products_count)
                                        <span class="bg-gray-950 text-gray-50 border border-gray-700 text-[10px] font-mono uppercase px-2.5 py-1">
                                            {{ $collection->products_count }} Prodotti
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Content --}}
                            <div class="p-6 md:p-8">
                                <h3 class="text-2xl font-black uppercase tracking-tight text-gray-950 mb-3 group-hover:text-accent-600 transition-colors">
                                    {{ $collection->name }}
                                </h3>

                                @if($collection->description)
                                    <p class="text-gray-600 text-sm leading-relaxed mb-6 font-sans">
                                        {{ Str::limit(strip_tags($collection->description), 140) }}
                                    </p>
                                @endif

                                {{-- Products preview inside bundle --}}
                                @if($collection->products->isNotEmpty())
                                    <div class="pt-4 border-t border-gray-200">
                                        <span class="text-[10px] font-mono uppercase tracking-widest text-gray-400 block mb-3">Include:</span>
                                        <ul class="space-y-1.5">
                                            @foreach($collection->products->take(3) as $product)
                                                <li class="flex items-center text-xs font-semibold text-gray-800 gap-2">
                                                    <span class="material-symbols-outlined text-accent-500 text-sm">check_circle</span>
                                                    <span class="truncate">{{ $product->name }}</span>
                                                </li>
                                            @endforeach
                                            @if($collection->products->count() > 3)
                                                <li class="text-[11px] font-mono text-gray-400 pl-5">
                                                    + altri {{ $collection->products->count() - 3 }} articoli nel kit
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Footer CTA --}}
                        <div class="p-6 md:px-8 md:pb-8 pt-0">
                            <a href="{{ route('collections.show', $collection->slug) }}"
                               class="w-full bg-gray-950 group-hover:bg-accent-600 text-gray-50 py-3.5 px-6 font-mono text-xs font-bold uppercase tracking-widest transition-colors flex items-center justify-center gap-3">
                                Scopri il Kit Completo
                                <span class="material-symbols-outlined text-sm">arrow_forward</span>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif
