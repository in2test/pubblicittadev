@props(['categories'])

<!-- Sezione 1: Missione (Layout Audace) -->
<section class="py-24 bg-grid-subtle">
    <div class="max-w-screen-2xl mx-auto px-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
            <div class="sticky top-32">
                <h2 class="text-sm font-bold uppercase tracking-widest text-primary mb-4 flex items-center">
                    <span class="w-8 h-[1px] bg-primary mr-4"></span>
                    I Servizi più richiesti
                </h2>
                <p class="text-4xl font-bold tracking-tight text-on-surface leading-tight">
                    Partiamo da ciò che i clienti cercano più spesso: materiali semplici da ordinare, facili da capire e subito utili per lavorare o promuoversi.
                </p>
            </div>
            <div class="space-y-12">
                <div class="border-l-2 border-primary-container pl-8 py-2">
                    <h3 class="text-xl font-bold mb-4">Biglietti da visita</h3>
                    <p class="text-secondary leading-relaxed">
                        Per professionisti, attività locali, nuove aperture ed eventi. Se il file è già pronto, lo verifichiamo e procediamo alla stampa. Se invece manca una grafica pulita o un logo adatto, ti aiutiamo a prepararlo.
                    </p>
                </div>
                <div class="border-l-2 border-outline pl-8 py-2">
                    <h3 class="text-xl font-bold mb-4">T-shirt personalizzate</h3>
                    <p class="text-secondary leading-relaxed">
                        Per aziende, staff, associazioni, locali ed eventi. Una soluzione pratica per rendere visibile il tuo brand e dare coerenza all’immagine del tuo team. PubbliCittà24 propone anche cataloghi dedicati all’abbigliamento personalizzabile.
                    </p>
                </div>
                <div class="border-l-2 border-outline pl-8 py-2">
                    <h3 class="text-xl font-bold mb-4">Grafica base per la stampa</h3>
                    <p class="text-secondary leading-relaxed">
                        Quando il cliente ha un’idea ma non il materiale corretto, interveniamo con logo semplice, adattamenti grafici, impaginazioni base e file pronti per la produzione .
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>


<section class="py-24 px-8 mx-auto bg-surface-container-lowest">
    <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-8">
        <div>
            <span class="font-mono text-xs text-primary font-bold uppercase tracking-widest mb-4 block">Divisioni
                Operative</span>
            <h2 class="text-4xl font-black tracking-tight uppercase">COME FUNZIONA</h2>
        </div>
        <div class="max-w-md text-right">
            <p class="text-secondary font-mono text-sm leading-relaxed">Abbiamo reso il processo semplice anche per chi non è del mestiere.</p>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-4 xl:grid-cols-6 gap-4 h-full md:h-150">
        @foreach ($categories as $index => $category)
        @php
        $styleIndex = $index % 4;
        $imageUrl = $category->image?->image_url ?? ($category->image?->image_path ? asset('storage/' . $category->image?->image_path) : 'https://placehold.co/800x600?text=' . urlencode($category->name));
        @endphp

        @if ($styleIndex === 0)
        <!-- Style 1: Featured Image (Wide) -->
        <div class="md:col-span-2 group relative overflow-hidden bg-zinc-100 flex flex-col justify-end p-8">
            <img alt="{{ $category->name }}"
                class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                src="{{ $imageUrl }}" />
            <div class="absolute inset-0 bg-zinc-900/40 group-hover:bg-zinc-900/20 transition-colors"></div>
            <div class="relative z-10 text-white">
                <h3 class="text-3xl font-black mb-2">{{ $category->name }}</h3>
                <p class="mb-6 max-w-xs opacity-80">{{ $category->description ?? 'Soluzioni su misura per il tuo business.' }}</p>
                <a class="inline-flex items-center gap-2 font-bold tracking-widest text-xs uppercase hover:gap-4 transition-all"
                    href="{{ route('category',  ['category' => $category->slug]) }}">Scoprili <span class="material-symbols-outlined text-sm">east</span></a>
            </div>
        </div>
        @elseif ($styleIndex === 1)
        <!-- Style 2: Iconic (Standard) -->
        <div class="group relative overflow-hidden bg-surface-container-high flex flex-col p-8 border-l-4 border-primary">
            <div class="relative z-10">
                <span class="material-symbols-outlined text-4xl text-primary mb-6">
                    @if(str_contains(strtolower($category->name), 'stampa')) print @elseif(str_contains(strtolower($category->name), 'digital')) digital_out_of_home @else category @endif
                </span>
                <h3 class="text-xl font-bold mb-4 uppercase">{{ $category->name }}</h3>
                <p class="text-sm text-secondary leading-relaxed mb-6">{{ Str::limit($category->description, 80) ?? 'Qualità e precisione in ogni dettaglio.' }}</p>
                <div class="mt-auto">
                    <span class="font-mono text-[10px] bg-white px-2 py-1">PREMIUM_QUALITY</span>
                </div>
            </div>
        </div>
        @elseif ($styleIndex === 2)
        <!-- Style 3: Minimal/Dark (Standard) -->
        <div class="group relative overflow-hidden bg-zinc-800 flex flex-col justify-between p-8 text-white">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <span class="text-6xl font-black">0{{ $index + 1 }}</span>
            </div>
            <h3 class="text-xl font-bold uppercase">{{ $category->name }}</h3>
            <div class="space-y-2 font-mono text-xs opacity-60">
                <p>{{ $category->description ?? 'Servizi professionali dedicati.' }}</p>
            </div>
            <a href="{{ route('category', ['category' => $category->slug]) }}"
                class="w-full py-3 border border-white/20 hover:bg-white hover:text-zinc-900 transition-colors text-center text-[10px] font-bold tracking-widest uppercase">Dettagli</a>
        </div>
        @elseif ($styleIndex === 3)
        <!-- Style 4: Bento Info (Wide) -->
        <div class="md:col-span-2 group relative overflow-hidden bg-surface-container-lowest p-12 flex items-center border border-surface-container">
            <div class="flex-1">
                <h3 class="text-4xl font-black mb-6 uppercase tracking-tighter">{{ $category->name }}</h3>
                <p class="text-secondary max-w-sm mb-8">{{ $category->description ?? 'Esperienza e innovazione al servizio del tuo brand.' }}</p>
                <ul class="grid grid-cols-2 gap-y-3 font-mono text-[11px] text-primary font-bold">
                    <li class="flex items-center gap-2"><span class="w-1 h-1 bg-primary"></span>DESIGN PURE</li>
                    <li class="flex items-center gap-2"><span class="w-1 h-1 bg-primary"></span>TECH DRIVEN</li>
                    <li class="flex items-center gap-2"><span class="w-1 h-1 bg-primary"></span>BRANDING</li>
                    <li class="flex items-center gap-2"><span class="w-1 h-1 bg-primary"></span>SUPPORT</li>
                </ul>
            </div>
            <div class="hidden lg:block w-1/3 text-vertical text-[10px] font-mono tracking-[0.5em] text-surface-dim uppercase select-none">
                {{ strtoupper(str_replace(' ', '_', $category->name)) }}
            </div>
        </div>
        @endif
        @endforeach
    </div>
</section>

<section class="py-24 px-8 mx-auto bg-surface-container-lowest">
    <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-8">
        <div>
            <span class="font-mono text-xs text-primary font-bold uppercase tracking-widest mb-4 block">Divisioni
                Operative</span>
            <h2 class="text-4xl font-black tracking-tight uppercase">COME FUNZIONA</h2>
        </div>
        <div class="max-w-md text-right">
            <p class="text-secondary font-mono text-sm leading-relaxed">Abbiamo reso tutto semplice, anche per chi non è del mestiere, senza complicazioni tecniche.</p>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-4 xl:grid-cols-6 gap-4 h-full md:h-150">

        <div class="md:col-span-2 group relative overflow-hidden bg-zinc-100 flex flex-col justify-end p-8">

            <div class="absolute inset-0 bg-zinc-900/40 group-hover:bg-zinc-900/20 transition-colors"></div>
            <div class="relative z-10 text-white">
                <h3 class="text-3xl font-black mb-2">1: Scegli il tuo prodotto</h3>
                <p class="mb-6 max-w-xs opacity-80">Consulta il catalogo e individua quello che ti serve davvero: biglietti da visita, t-shirt, volantini, pannelli, insegne o altri materiali personalizzati .</p>
            </div>
        </div>


        <div class="group relative overflow-hidden bg-zinc-800 flex flex-col justify-between p-8 text-white">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <span class="text-6xl font-black">3</span>
            </div>
            <h3 class="text-xl font-bold uppercase">3: Invia il file e attendi la conferma</h3>
            <div class="space-y-2 font-mono text-xs opacity-60">
                <p>Una volta caricato il file, il nostro team lo controlla gratuitamente. Se è tutto corretto, avviamo la produzione. Altrimenti ti contatteremo per sistemare insieme eventuali problemi.</p>
            </div>

        </div>
        <!-- Style 4: Bento Info (Wide) -->
        <div class="md:col-span-2 group relative overflow-hidden bg-surface-container-lowest p-12 flex items-center border border-surface-container">
            <div class="flex-1">
                <h3 class="text-4xl font-black mb-6 uppercase tracking-tighter">Ricevi il tuo prodotto personalizzato</h3>
                <p class="text-secondary max-w-sm mb-8">Dopo la conferma, passiamo alla produzione e spediamo in tutta Italia. Per progetti più complessi in Centro Italia possiamo offrirti anche un supporto diretto sul posto .</p>
                <ul class="grid grid-cols-2 gap-y-3 font-mono text-[11px] text-primary font-bold">
                    <li class="flex items-center gap-2"><span class="w-1 h-1 bg-primary"></span>DESIGN PURE</li>
                    <li class="flex items-center gap-2"><span class="w-1 h-1 bg-primary"></span>TECH DRIVEN</li>
                    <li class="flex items-center gap-2"><span class="w-1 h-1 bg-primary"></span>BRANDING</li>
                    <li class="flex items-center gap-2"><span class="w-1 h-1 bg-primary"></span>SUPPORT</li>
                </ul>
            </div>

        </div>

    </div>
</section>