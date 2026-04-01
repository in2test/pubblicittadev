@props(['categories'])

<!-- Sezione 1: Missione (Layout Audace) -->
<section class="py-24 bg-grid-subtle">
    <div class="mx-auto px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
            <div class="sticky top-32">
                <h2 class="text-sm font-bold uppercase tracking-widest text-primary mb-4 flex items-center">
                    <span class="w-8 h-[1px] bg-primary mr-4"></span>
                    I Servizi più richiesti
                </h2>
                <p class="text-4xl font-bold tracking-tight text-on-surface leading-tight">
                    Partiamo da ciò che i clienti cercano più spesso: materiali semplici da ordinare, facili da capire e
                    subito utili per lavorare o promuoversi.
                </p>
            </div>
            <div class="space-y-12">
                <div class="border-l-2 border-primary-container pl-8 py-2">
                    <h3 class="text-xl font-bold mb-4">Biglietti da visita</h3>
                    <p class="text-secondary leading-relaxed">
                        Per professionisti, attività locali, nuove aperture ed eventi. Se il file è già pronto, lo
                        verifichiamo e procediamo alla stampa. Se invece manca una grafica pulita o un logo adatto, ti
                        aiutiamo a prepararlo.
                    </p>
                </div>
                <div class="border-l-2 border-outline pl-8 py-2">
                    <h3 class="text-xl font-bold mb-4">T-shirt personalizzate</h3>
                    <p class="text-secondary leading-relaxed">
                        Per aziende, staff, associazioni, locali ed eventi. Una soluzione pratica per rendere visibile
                        il tuo brand e dare coerenza all’immagine del tuo team. PubbliCittà24 propone anche cataloghi
                        dedicati all’abbigliamento personalizzabile.
                    </p>
                </div>
                <div class="border-l-2 border-outline pl-8 py-2">
                    <h3 class="text-xl font-bold mb-4">Grafica base per la stampa</h3>
                    <p class="text-secondary leading-relaxed">
                        Quando il cliente ha un’idea ma non il materiale corretto, interveniamo con logo semplice,
                        adattamenti grafici, impaginazioni base e file pronti per la produzione .
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>




<section class="py-24 px-8 mx-auto bg-surface-container-lowest">
    <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-8">
        <div>
            <span class="font-mono text-xs text-primary font-bold uppercase tracking-widest mb-4 block">
                PRODOTTO->TEMPLATE->CONTROLLO->SPEDIZIONE</span>
            <h2 class="text-4xl font-black tracking-tight uppercase">COME FUNZIONA</h2>
        </div>
        <div class="max-w-md text-right">
            <p class="text-secondary font-mono text-sm leading-relaxed">Abbiamo reso tutto semplice, anche per chi non è
                del mestiere, senza complicazioni tecniche.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 xl:grid-cols-6 gap-4 h-full md:h-150">

        <div class="md:col-span-2 group relative overflow-hidden bg-zinc-100 flex flex-col justify-end p-8">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <span class="text-6xl font-black">1</span>
            </div>

            <div class="absolute inset-0 bg-zinc-900/40 group-hover:bg-zinc-900/20 transition-colors"></div>
            <div class="relative z-10 text-white">
                <h3 class="text-3xl font-black mb-2">1: Scegli il tuo prodotto</h3>
                <p class="mb-6 max-w-xs opacity-80">Consulta il catalogo e individua quello che ti serve davvero:
                    biglietti da visita, t-shirt, volantini, pannelli, insegne o altri materiali personalizzati .</p>
                <div class="mt-auto">
                    <a href="{{ route('categories') }}"
                        class="font-mono text-xs font-bold bg-white px-2 py-1 text-primary hover:bg-primary hover:text-white transition-colors uppercase">Catalogo
                        completo</a>
                </div>
            </div>
        </div>
        <!-- Style 2: Iconic (Standard) -->
        <div
            class="group relative overflow-hidden bg-surface-container-high flex flex-col p-8 border-l-4 border-primary">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <span class="text-6xl font-black">2</span>
            </div>
            <div class="relative z-10">
                <h3 class="text-xl font-bold mb-4 uppercase">Scarica il template e aggiungi la tua grafica</h3>
                <p class="text-sm text-secondary leading-relaxed mb-6">Se hai già il materiale pronto, puoi usare il
                    template corretto per preparare il file con misure e impostazioni giuste. Se non hai ancora la
                    grafica, puoi contattarci direttamente e ti aiutiamo noi.</p>

            </div>
        </div>


        <div class="group relative overflow-hidden bg-zinc-800 flex flex-col justify-between p-8 text-white">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <span class="text-6xl font-black">3</span>
            </div>
            <h3 class="text-xl font-bold uppercase">3: Invia il file e attendi la conferma</h3>
            <div class="space-y-2 font-mono text-xs opacity-60">
                <p>Una volta caricato il file, il nostro team lo controlla gratuitamente. Se è tutto corretto, avviamo
                    la produzione. Altrimenti ti contatteremo per sistemare insieme eventuali problemi.</p>
            </div>

        </div>
        <!-- Style 4: Bento Info (Wide) -->
        <div
            class="md:col-span-2 group relative overflow-hidden bg-surface-container-lowest p-12 flex items-center border border-surface-container">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <span class="text-6xl font-black">4</span>
            </div>
            <div class="flex-1">
                <h3 class="text-4xl font-black mb-6 uppercase tracking-tighter">Ricevi il tuo prodotto personalizzato
                </h3>
                <p class="text-secondary max-w-sm mb-8">Dopo la conferma, passiamo alla produzione e spediamo in tutta
                    Italia. Per progetti più complessi in Centro Italia possiamo offrirti anche un supporto diretto sul
                    posto .</p>
                <ul class="grid grid-cols-2 gap-y-3 font-mono text-[11px] text-primary font-bold">
                    <li class="flex items-center gap-2"><span class="w-1 h-1 bg-primary"></span>PRODUZIONE</li>
                    <li class="flex items-center gap-2"><span class="w-1 h-1 bg-primary"></span>SPEDIZIONE</li>
                    <li class="flex items-center gap-2"><span class="w-1 h-1 bg-primary"></span>CONSEGNA</li>
                    <li class="flex items-center gap-2"><span class="w-1 h-1 bg-primary"></span>ASSISTENZA</li>
                </ul>
            </div>

        </div>

    </div>
</section>