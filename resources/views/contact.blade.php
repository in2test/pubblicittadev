<x-layout>

    <div class="industrial-grid absolute inset-0 pointer-events-none"></div>
    <div class="max-w-screen-2xl mx-auto px-6 py-16 md:py-24">
        <!-- Hero Header -->
        <div class="mb-16 md:mb-24">
            <span class="font-mono text-xs uppercase tracking-[0.3em] text-primary mb-4 block">Communication
                Protocol</span>
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
                                <p class="text-xl font-bold tracking-tight">Via dell'Industria, 42<br />20100 Milano
                                    (MI), Italia</p>
                            </div>
                        </div>
                        <div class="flex gap-6 items-start">
                            <div class="w-12 h-12 bg-surface-container flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-primary" data-icon="call">call</span>
                            </div>
                            <div>
                                <h3 class="font-mono text-xs uppercase tracking-widest text-secondary mb-2">Reparto
                                    Vendite</h3>
                                <p class="text-xl font-bold tracking-tight">+39 02 1234 5678</p>
                                <p class="text-sm text-secondary">commerciale@officinapro.it</p>
                            </div>
                        </div>
                        <div class="flex gap-6 items-start">
                            <div class="w-12 h-12 bg-surface-container flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-primary" data-icon="build">build</span>
                            </div>
                            <div>
                                <h3 class="font-mono text-xs uppercase tracking-widest text-secondary mb-2">Assistenza
                                    Tecnica</h3>
                                <p class="text-xl font-bold tracking-tight">+39 02 1234 5679</p>
                                <p class="text-sm text-secondary">supporto@officinapro.it</p>
                            </div>
                        </div>
                    </div>
                </section>
                <section class="bg-surface-container p-8">
                    <h3 class="font-mono text-xs uppercase tracking-widest text-secondary mb-4">Orari Operativi</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between border-b border-outline-variant/30 pb-2">
                            <span>Lun - Ven</span>
                            <span class="font-bold">08:30 — 18:00</span>
                        </div>
                        <div class="flex justify-between border-b border-outline-variant/30 pb-2">
                            <span>Sabato</span>
                            <span class="font-bold">09:00 — 13:00</span>
                        </div>
                        <div class="flex justify-between text-secondary/60">
                            <span>Domenica</span>
                            <span>Chiuso</span>
                        </div>
                    </div>
                </section>
                <!-- Styled Map Placeholder -->
                <div
                    class="relative h-64 w-full grayscale contrast-125 border border-outline-variant overflow-hidden group">
                    <div
                        class="absolute inset-0 bg-primary/5 group-hover:bg-transparent transition-colors z-10 pointer-events-none">
                    </div>
                    <img alt="Technical map view of industrial district" class="w-full h-full object-cover"
                        data-alt="Technical map view of industrial district" data-location="Milano"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuBYGJrboMpsfLGJC8PqUvDTTnMW2m01J8oDLwwNXIXK4s8o8xAN2P4538k5hdAAtpPVPqo53SX8qBF5NoA8zRNyONy34VOMa6bO-hvcIh8JO0SVzIdXNPDtShYePK9ac6MNlZW2zTbPzKR99QLfV0OvhPrWbw8FapwKqt41-dRzdQBLAh1R-BrRZrj8I5BJBVTLtGK13AcpPfT7ppdFxcXnEJMNvFeINvHfv9JM7cW6l28ptIBgGtsVW7YkEDWTh3H-igy0y4TwQa8" />
                    <div class="absolute bottom-4 left-4 bg-white px-4 py-2 shadow-xl z-20">
                        <span class="font-mono text-[10px] uppercase tracking-tighter">Lat: 45.4642 | Long:
                            9.1900</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-layout>
