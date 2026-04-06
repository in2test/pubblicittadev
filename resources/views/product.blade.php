<x-layout>

    <!-- Breadcrumbs -->
    <nav
        class="px-8 3xl:px-32 py-4 flex items-center gap-2 mb-12 text-xs font-mono uppercase tracking-widest text-secondary">
        <a class="hover:text-primary transition-colors" href="{{ route('home') }}">Home</a>
        <span class="material-symbols-outlined text-[10px]">chevron_right</span>
        @if ($category->parent)
            <a class="hover:text-primary transition-colors"
                href="{{ route('category', $category->parent->slug) }}">{{ $category->parent->name }}</a>
            <span class="material-symbols-outlined text-[10px]">chevron_right</span>
        @endif
        @if ($category)
            <a class="hover:text-primary transition-colors"
                href="{{ route('category', $category->slug) }}">{{ $category->name }}</a>
            <span class="material-symbols-outlined text-[10px]">chevron_right</span>
        @endif
        <span class="text-on-surface font-bold">{{ $product->name }}</span>
    </nav>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 px-8 3xl:px-32">
        <!-- Left Column: Gallery -->
        @php
            $firstMedia = $product->getFirstMedia('images');
            $mainImageUrl = $firstMedia
                ? $firstMedia->getUrl('large')
                : 'https://placehold.co/600x800?text=' . urlencode($product->name);
            $mainImageAlt = $firstMedia ? $firstMedia->name : $product->name;
        @endphp
        <div class="lg:col-span-7 space-y-4">
            <!-- Main Product Image -->
            <div class="aspect-4/5 bg-surface-container-lowest border border-outline-variant/10 overflow-hidden">
                <picture>
                    @if ($firstMedia)
                        <source media="(max-width: 768px)" srcset="{{ $firstMedia->getUrl('medium') }}">
                    @endif
                    <img alt="{{ $mainImageAlt }}" class="w-full h-full object-cover product-main-image"
                        data-alt="{{ $mainImageAlt }}" src="{{ $mainImageUrl }}" />
                </picture>
            </div>
            <!-- Thumbnail Gallery -->
            <div class="grid grid-cols-4 gap-4">
                @foreach ($product->getMedia('images') as $media)
                    @php
                        $thumbUrl = $media->getUrl('thumbnail');
                        $mediumUrl = $media->getUrl('medium');
                        $largeUrl = $media->getUrl('large');
                    @endphp
                    <div class="aspect-square bg-surface-container border-2 border-primary overflow-hidden image-thumbnail cursor-pointer"
                        onclick="changeMainImage('{{ $mediumUrl }}', '{{ $largeUrl }}', '{{ $media->name ?? $product->name }}')">
                        <img alt="{{ $media->name }}"
                            class="w-full h-full object-cover opacity-80 hover:opacity-100 transition-opacity thumbnail-image"
                            data-alt="{{ $media->name }}" src="{{ $thumbUrl }}" />
                    </div>
                @endforeach
            </div>
        </div>
        <!-- Right Column: Info & Config -->
        <div class="lg:col-span-5 flex flex-col">
            <div class="mb-2">
                <span class="font-mono text-[10px] tracking-tighter text-secondary bg-surface-container px-2 py-1">SKU:
                    {{ $product->sku }}</span>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black tracking-tighter text-on-surface mb-4 leading-none uppercase">
                {{ $product->name }}
            </h1>
            <div class="flex items-baseline gap-4 mb-8">
                <span class="text-3xl font-light text-primary tracking-tight">{{ $product->price }}</span>
                <span class="text-xs font-mono text-secondary">IVA INCLUSA</span>
            </div>
            <div class="mb-8 p-6 bg-surface-container-low border-l-4 border-primary">
                <p class="text-sm text-on-surface-variant leading-relaxed">
                    {{ $product->description }}
                </p>
            </div>
            <!-- Configuration Options -->
            <div class="space-y-8 mb-10">
                <!-- Color Selection -->
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">Colore
                        Disponibile</label>
                    <div class="flex gap-4">
                        <div
                            class="w-10 h-10 border-2 border-primary ring-2 ring-transparent transition-all cursor-pointer bg-black">
                        </div>
                        <div
                            class="w-10 h-10 border border-outline-variant/20 hover:border-primary transition-all cursor-pointer bg-[#1B263B]">
                        </div>
                        <div
                            class="w-10 h-10 border border-outline-variant/20 hover:border-primary transition-all cursor-pointer bg-[#750005]">
                        </div>
                    </div>
                </div>
                <!-- Size Selection -->
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">Taglia
                        (EU Standard)</label>
                    <div class="grid grid-cols-5 gap-2">
                        <button
                            class="py-3 border border-outline-variant/20 font-mono text-xs hover:bg-primary hover:text-white hover:border-primary transition-all">S</button>
                        <button
                            class="py-3 border-2 border-primary font-mono text-xs bg-surface-container-lowest text-on-surface">M</button>
                        <button
                            class="py-3 border border-outline-variant/20 font-mono text-xs hover:bg-primary hover:text-white hover:border-primary transition-all">L</button>
                        <button
                            class="py-3 border border-outline-variant/20 font-mono text-xs hover:bg-primary hover:text-white hover:border-primary transition-all">XL</button>
                        <button
                            class="py-3 border border-outline-variant/20 font-mono text-xs hover:bg-primary hover:text-white hover:border-primary transition-all">XXL</button>
                    </div>
                </div>
                <!-- Quantity -->
                <div class="flex items-center gap-6">
                    <div class="flex-1">
                        <label
                            class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">Quantità</label>
                        <div class="flex items-center border border-outline-variant/20 w-32 h-12">
                            <button
                                class="w-10 h-full flex items-center justify-center hover:bg-surface-container transition-colors">
                                <span class="material-symbols-outlined text-sm">remove</span>
                            </button>
                            <input class="flex-1 border-none bg-transparent text-center font-mono text-sm focus:ring-0"
                                type="number" value="1" />
                            <button
                                class="w-10 h-full flex items-center justify-center hover:bg-surface-container transition-colors">
                                <span class="material-symbols-outlined text-sm">add</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- CTAs -->
            <div class="space-y-3">
                <button
                    class="w-full bg-primary-container text-on-primary py-5 px-8 font-bold text-sm tracking-widest uppercase flex items-center justify-center gap-3 transition-transform active:scale-[0.98]">
                    <span class="material-symbols-outlined text-lg" data-icon="shopping_cart">shopping_cart</span>
                    Aggiungi al Carrello
                </button>
                <button
                    class="w-full border border-on-surface/20 text-on-surface py-5 px-8 font-mono text-xs tracking-widest uppercase flex items-center justify-center gap-3 hover:bg-surface-container transition-colors">
                    <span class="material-symbols-outlined text-lg" data-icon="description">description</span>
                    Scarica Scheda Tecnica
                </button>
            </div>
            <!-- Trust Badges -->
            <div
                class="mt-12 pt-8 border-t border-outline-variant/20 flex items-center gap-8 opacity-60 filter grayscale hover:grayscale-0 transition-all duration-500">
                <div class="flex items-center gap-2">
                    <div
                        class="w-8 h-8 border border-on-surface/40 flex items-center justify-center text-[8px] font-bold">
                        ISO 9001</div>
                    <span class="text-[10px] font-mono leading-tight">Certificazione<br />Qualità</span>
                </div>
                <div class="flex items-center gap-2">
                    <div
                        class="w-8 h-8 border border-on-surface/40 flex items-center justify-center text-[8px] font-bold">
                        EN 20471</div>
                    <span class="text-[10px] font-mono leading-tight">Standard<br />Sicurezza</span>
                </div>
            </div>
        </div>
    </div>
    <!-- Technical Specs Section -->
    <section class="my-24 px-8 3xl:px-32">
        <div class="mb-12">
            <h2 class="text-3xl font-black tracking-tighter uppercase mb-2">Specifiche Tecniche</h2>
            <div class="h-1 w-24 bg-primary"></div>
        </div>
        <div
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-px bg-outline-variant/20 border border-outline-variant/20">
            <div class="bg-surface p-8">
                <span class="material-symbols-outlined text-primary mb-4" data-icon="water_drop">water_drop</span>
                <h3 class="text-[10px] font-mono text-secondary uppercase tracking-widest mb-2">Impermeabilità
                </h3>
                <p class="font-mono text-xl font-bold">20.000 MM</p>
                <p class="text-xs text-secondary mt-2">Testato secondo standard ISO 811</p>
            </div>
            <div class="bg-surface p-8">
                <span class="material-symbols-outlined text-primary mb-4" data-icon="air">air</span>
                <h3 class="text-[10px] font-mono text-secondary uppercase tracking-widest mb-2">Traspirabilità
                </h3>
                <p class="font-mono text-xl font-bold">15.000 G/M²</p>
                <p class="text-xs text-secondary mt-2">Tecnologia Pro-Vent Active</p>
            </div>
            <div class="bg-surface p-8">
                <span class="material-symbols-outlined text-primary mb-4" data-icon="layers">layers</span>
                <h3 class="text-[10px] font-mono text-secondary uppercase tracking-widest mb-2">Composizione
                </h3>
                <p class="font-mono text-xl font-bold">100% NYLON RIPSTOP</p>
                <p class="text-xs text-secondary mt-2">Membrana in PTFE espanso</p>
            </div>
            <div class="bg-surface p-8">
                <span class="material-symbols-outlined text-primary mb-4"
                    data-icon="local_laundry_service">local_laundry_service</span>
                <h3 class="text-[10px] font-mono text-secondary uppercase tracking-widest mb-2">Manutenzione
                </h3>
                <p class="font-mono text-xl font-bold">40°C LAVAGGIO</p>
                <p class="text-xs text-secondary mt-2">Non utilizzare ammorbidenti</p>
            </div>
        </div>
        <div class="mt-12 grid grid-cols-1 lg:grid-cols-2 gap-12">
            <div class="space-y-4">
                <h4 class="font-bold text-lg border-b-2 border-surface-container inline-block pb-1">
                    Caratteristiche Costruttive</h4>
                <ul class="space-y-3">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary text-sm mt-1">check_circle</span>
                        <span class="text-sm">Cuciture termonastrate a triplo strato per isolamento
                            totale.</span>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary text-sm mt-1">check_circle</span>
                        <span class="text-sm">Zip YKK® Aquaguard® idrorepellenti ad alta resistenza.</span>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary text-sm mt-1">check_circle</span>
                        <span class="text-sm">Rinforzi in Cordura® su gomiti e zone ad alta usura.</span>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary text-sm mt-1">check_circle</span>
                        <span class="text-sm">Cappuccio regolabile compatibile con caschi di protezione.</span>
                    </div>
                </ul>
            </div>
            <div class="bg-surface-container-low p-8 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-5">
                    <span class="material-symbols-outlined text-9xl">architecture</span>
                </div>
                <h4 class="font-bold text-lg mb-4">Note per la Personalizzazione</h4>
                <p class="text-sm leading-relaxed mb-6">
                    Il Guscio Pro-X supporta la stampa a caldo e il ricamo tecnico. Consigliamo il
                    posizionamento dei loghi aziendali sul petto sinistro o sulla schiena per mantenere
                    l'integrità della membrana impermeabile.
                </p>
                <a class="text-primary font-bold text-xs uppercase tracking-widest flex items-center gap-2 hover:gap-4 transition-all"
                    href="#">
                    Configura Logo Aziendale
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>
        </div>
    </section>

    <script>
        function changeMainImage(mediumSrc, largeSrc, altText) {
            const mainImg = document.querySelector('.product-main-image');
            const source = document.querySelector('source[media="(max-width: 768px)"]');

            mainImg.src = largeSrc;
            mainImg.alt = altText;
            mainImg.setAttribute('data-alt', altText);

            if (source) {
                source.srcset = mediumSrc;
            }
        }
    </script>

</x-layout>
