<x-layout>

    <div class="industrial-grid absolute inset-0 pointer-events-none"></div>
    <div class="mx-auto px-8 3xl:px-32 py-16 md:py-24">
        <!-- Hero Header -->
            <div class="mb-16 md:mb-24">
            <span class="font-mono text-xs uppercase tracking-[0.3em] text-primary mb-4 block">Protocollo di Comunicazione</span>
            <h1 class="text-5xl md:text-7xl font-black tracking-tighter text-on-surface leading-tight max-w-4xl">
                Supporto Tecnico <br />e Commerciale
            </h1>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-24">
            <!-- Left Column: Contact Form -->
            <div class="lg:col-span-7">
                <div class="bg-surface-container-lowest p-8 md:p-12 border-l-4 border-primary">
                    <form action="#" class="space-y-10">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                            <div class="relative group">
                                <label
                                    class="block font-mono text-[10px] uppercase tracking-widest text-secondary mb-2">Nome
                                    Completo</label>
                                <input
                                    class="w-full bg-transparent border-0 border-b border-outline-variant focus:ring-0 focus:border-primary px-0 py-2 text-on-surface placeholder:text-outline-variant transition-all"
                                    placeholder="Es. Mario Rossi" type="text" />
                            </div>
                            <div class="relative group">
                                <label
                                    class="block font-mono text-[10px] uppercase tracking-widest text-secondary mb-2">Azienda</label>
                                <input
                                    class="w-full bg-transparent border-0 border-b border-outline-variant focus:ring-0 focus:border-primary px-0 py-2 text-on-surface placeholder:text-outline-variant transition-all"
                                    placeholder="Nome Società S.p.A." type="text" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                            <div class="relative group">
                                <label
                                    class="block font-mono text-[10px] uppercase tracking-widest text-secondary mb-2">Indirizzo
                                    Email</label>
                                <input
                                    class="w-full bg-transparent border-0 border-b border-outline-variant focus:ring-0 focus:border-primary px-0 py-2 text-on-surface placeholder:text-outline-variant transition-all"
                                    placeholder="m.rossi@azienda.it" type="email" />
                            </div>
                            <div class="relative group">
                                <label
                                    class="block font-mono text-[10px] uppercase tracking-widest text-secondary mb-2">Reparto
                                    di Interesse</label>
                                <select
                                    class="w-full bg-transparent border-0 border-b border-outline-variant focus:ring-0 focus:border-primary px-0 py-2 text-on-surface transition-all appearance-none">
                                    <option>Commerciale</option>
                                    <option>Assistenza Tecnica</option>
                                    <option>Logistica</option>
                                    <option>Amministrazione</option>
                                </select>
                            </div>
                        </div>
                        <div class="relative group">
                            <label
                                class="block font-mono text-[10px] uppercase tracking-widest text-secondary mb-2">Messaggio
                                Tecnico</label>
                            <textarea
                                class="w-full bg-transparent border-0 border-b border-outline-variant focus:ring-0 focus:border-primary px-0 py-2 text-on-surface placeholder:text-outline-variant transition-all resize-none"
                                placeholder="Descriva la sua richiesta nei dettagli..." rows="4"></textarea>
                        </div>
                        
                        <label class="flex items-start gap-3 mt-8 cursor-pointer">
                            <input type="checkbox" required class="mt-1 border-gray-300 rounded text-primary focus:ring-primary">
                            <span class="text-sm font-mono text-gray-600 leading-tight">
                                Ho letto l’<a href="{{ route('privacy') }}" class="text-primary hover:underline">Informativa Privacy</a> e acconsento al trattamento dei miei dati per la gestione della richiesta.
                            </span>
                        </label>

                        <div class="pt-6">
                            <button
                                class="bg-primary hover:bg-primary-container text-on-primary font-mono text-sm uppercase tracking-widest px-10 py-5 transition-all duration-300 flex items-center gap-4 group"
                                type="submit">
                                Invia Richiesta
                                <span
                                    class="material-symbols-outlined text-sm group-hover:translate-x-1 transition-transform"
                                    data-icon="arrow_forward">arrow_forward</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Right Column: Direct References -->
            <div class="lg:col-span-5 space-y-16">
                <section class="space-y-8">
                    <div class="space-y-6">
                        <div class="flex gap-6 items-start">
                            <div class="w-12 h-12 bg-surface-container flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-primary" data-icon="factory">factory</span>
                            </div>
                            <div>
                                <h3 class="font-mono text-xs uppercase tracking-widest text-secondary mb-2">Sede
                                    Centrale</h3>
                                <p class="text-xl font-bold tracking-tight">SS 155 per Fiuggi, 128<br />03010 Trivigliano (FR), Italia</p>
                            </div>
                        </div>
                        <div class="flex gap-6 items-start">
                            <div class="w-12 h-12 bg-surface-container flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-primary" data-icon="call">call</span>
                            </div>
                            <div>
                                <h3 class="font-mono text-xs uppercase tracking-widest text-secondary mb-2">Telefono e Vendite</h3>
                                <p class="text-xl font-bold tracking-tight">+39 0775 520 273</p>
                                <p class="text-sm text-secondary">info@pubblicitta24.it</p>
                            </div>
                        </div>
                        <div class="flex gap-6 items-start">
                            <div class="w-12 h-12 bg-surface-container flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-primary" data-icon="build">build</span>
                            </div>
                            <div>
                                <h3 class="font-mono text-xs uppercase tracking-widest text-secondary mb-2">Supporto Clienti</h3>
                                <p class="text-xl font-bold tracking-tight">+39 0775 520 273</p>
                                <p class="text-sm text-secondary">assistenza@pubblicitta24.it</p>
                            </div>
                        </div>
                    </div>
                </section>
                <section class="bg-surface-container p-8">
                    <h3 class="font-mono text-xs uppercase tracking-widest text-secondary mb-4">Orari Operativi</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between border-b border-outline-variant/30 pb-2">
                            <span>Lun - Ven</span>
                            <span class="font-bold">09:00 — 13:00 / 15:00 — 18:30</span>
                        </div>
                        <div class="flex justify-between border-b border-outline-variant/30 pb-2">
                            <span>Sabato</span>
                            <span class="font-bold">09:00 — 13:00</span>
                        </div>
                        <div class="flex justify-between text-secondary/60 mt-2">
                            <span>Domenica</span>
                            <span>Chiuso</span>
                        </div>
                    </div>
                </section>
                <!-- Styled Map Placeholder -->
                <div class="relative h-64 w-full grayscale hover:grayscale-0 contrast-125 border border-outline-variant overflow-hidden transition-all duration-500">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2970.627914041124!2d13.269293!3d41.7723186!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x132558ae53356aa1%3A0xd87cadd6464c2404!2sPubbliCitta&#39;%2024%20srls!5e0!3m2!1sit!2sit!4v1717325988583!5m2!1sit!2sit" 
                        class="w-full h-full border-0" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>
    </div>

</x-layout>
