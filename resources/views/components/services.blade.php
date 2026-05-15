@props(['categories'])

<!-- Sezione 1: Missione (Layout Audace) -->
<section class="py-24 bg-grid-subtle bg-gray-200">
    <div class="mx-auto px-8 3xl:px-32">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
            <div class="lg:sticky top-32">
                <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-900 mb-4 flex items-center">
                    <span class="w-8 h-px bg-vividauburn-500 mr-4"></span>
                    I Servizi più richiesti
                </h2>
                <p class="text-4xl font-bold tracking-tight text-gray-900 leading-tight">
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

<section class="py-24 px-8 3xl:px-32 mx-auto bg-white">
    <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-8">
        <div class="">
            <span class="text-sm font-mono font-bold uppercase tracking-widest text-vividauburn-500 mb-4 flex items-center">
                <span class="w-8 h-px bg-vividauburn-500 mr-4"></span>PRODOTTO -> TEMPLATE -> CONTROLLO -> SPEDIZIONE</span>
            <h2 class="text-4xl lg:text-5xl font-black tracking-tighter text-gray-950 leading-tight text-left">Come funziona</h2>
        </div>
        <div class="max-w-md md:text-right">
            <p class="text-gray-600 font-mono text-sm leading-relaxed">Abbiamo reso tutto semplice, anche per chi non è
                del mestiere, senza complicazioni tecniche.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <!-- Step 1 -->
        <div class="group relative overflow-hidden bg-gray-50 flex flex-col justify-between p-8 border border-gray-200 min-h-[300px]">
            <div class="absolute top-0 right-0 p-4 opacity-10 select-none pointer-events-none">
                <span class="text-8xl font-black text-gray-950">1</span>
            </div>
            <div class="relative z-10">
                <h3 class="text-2xl font-black mb-4 text-gray-900">Scegli il tuo prodotto</h3>
                <p class="text-gray-600 leading-relaxed">Consulta il catalogo e individua quello che ti serve davvero:
                    biglietti da visita, t-shirt, volantini o materiali personalizzati.</p>
            </div>
            <div class="relative z-10 mt-8">
                <a href="{{ route('catalog') }}"
                    class="inline-block font-mono text-xs font-bold bg-gray-950 px-4 py-2 text-white hover:bg-vividauburn-500 transition-colors uppercase">Catalogo completo</a>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="group relative overflow-hidden bg-gray-50 flex flex-col p-8 border border-gray-200 min-h-[300px]">
            <div class="absolute top-0 right-0 p-4 opacity-10 select-none pointer-events-none">
                <span class="text-8xl font-black text-gray-950">2</span>
            </div>
            <div class="relative z-10">
                <h3 class="text-2xl font-black mb-4 text-gray-900">Aggiungi la tua grafica</h3>
                <p class="text-gray-600 leading-relaxed">Usa i nostri template per preparare il file correttamente. Non hai la grafica? Contattaci e ti aiutiamo noi a crearla.</p>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="group relative overflow-hidden bg-gray-950 flex flex-col p-8 text-white min-h-[300px]">
            <div class="absolute top-0 right-0 p-4 opacity-20 select-none pointer-events-none">
                <span class="text-8xl font-black text-white">3</span>
            </div>
            <div class="relative z-10">
                <h3 class="text-2xl font-black mb-4">Controllo Gratuito</h3>
                <p class="opacity-80 leading-relaxed text-sm">Controlliamo ogni file gratuitamente. Se c'è qualche problema tecnico, ti contattiamo per risolverlo insieme prima di stampare.</p>
            </div>
        </div>

        <!-- Step 4 -->
        <div class="group relative overflow-hidden bg-gray-50 flex flex-col p-8 border border-gray-200 min-h-[300px]">
            <div class="absolute top-0 right-0 p-4 opacity-10 select-none pointer-events-none">
                <span class="text-8xl font-black text-gray-950">4</span>
            </div>
            <div class="relative z-10">
                <h3 class="text-2xl font-black mb-4 text-gray-900">Ricevi a casa</h3>
                <p class="text-gray-600 leading-relaxed mb-6">Produciamo in tempi rapidi e spediamo in tutta Italia con corriere espresso.</p>
                <ul class="grid grid-cols-2 gap-y-2 font-mono text-[10px] text-vividauburn-500 font-bold uppercase">
                    <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 bg-vividauburn-500 rounded-full"></span>Produzione</li>
                    <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 bg-vividauburn-500 rounded-full"></span>Qualità</li>
                    <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 bg-vividauburn-500 rounded-full"></span>Spedizione</li>
                    <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 bg-vividauburn-500 rounded-full"></span>Assistenza</li>
                </ul>
            </div>
        </div>
    </div>
</section>