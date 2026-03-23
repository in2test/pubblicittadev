<x-layout>
    <!-- Header & Search Bar Section -->
    <section class="mb-12">
        <div class="flex flex-col md:flex-row justify-between items-end gap-6 mb-8">
            <div>
                <h1 class="text-4xl md:text-5xl font-black tracking-tighter text-on-background uppercase">
                    Catalogo <span class="text-primary">Tecnico</span>
                </h1>
                <p class="text-secondary-fixed-dim font-mono text-sm mt-2">SPECIFICHE INDUSTRIALI /
                    PERFORMANCE ELEVATE</p>
            </div>
            <div class="text-right">
                <span class="text-3xl font-light tracking-tighter text-on-surface">48</span>
                <span class="text-xs uppercase tracking-widest text-secondary block">Prodotti trovati</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-1 shadow-sm flex items-center gap-2 border-b-2 border-primary">
            <span class="material-symbols-outlined px-3 text-secondary">search</span>
            <input class="flex-1 border-none focus:ring-0 text-sm bg-transparent py-4 font-body"
                placeholder="Filtra per nome prodotto, SKU o specifica tecnica..." type="text" />
            <button
                class="bg-surface-container hover:bg-surface-container-high px-6 py-3 text-xs font-bold uppercase tracking-widest transition-colors mr-1">Ordina
                per: Rilevanza</button>
        </div>
    </section>
    <!-- Product Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-8">
        <!-- Product Card 1 -->
        <article
            class="group bg-surface-container-lowest flex flex-col h-full border-b-4 border-transparent hover:border-primary transition-all duration-300">
            <div class="aspect-[4/5] overflow-hidden bg-surface-container relative">
                <img class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-500 group-hover:scale-105"
                    data-alt="Technical high-visibility safety jacket industrial worker"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuCdPXRiPmZkgtmttFDm3re0kfG_AV7BezQq2iI-MYV26B--evo3AUiu3tdqu9-Xz1PfZ9Hq8bm7uMVaqH5dMEMdUBc6n3H3bgAlnMo0uDoGS4WeWQVGILK_g88vPDVQIbLNSQUpH3Qvzgh44iKwPQWBPIofmuTFT6PtvB_o_ntO20q3UjjTsS9Emu2iyTa9suoTiLvTsb548vICiALTsHzgUXPIqWIBgnPmU_qg7npcPJ8ceaFxoLTb6T4B2GPLmyevg7ZO5LA7o8w" />
                <div class="absolute top-4 right-4">
                    <span class="bg-primary text-white text-[10px] font-bold px-2 py-1 uppercase tracking-tighter">New
                        Entry</span>
                </div>
            </div>
            <div class="p-6 flex flex-col flex-1">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="text-lg font-bold leading-tight uppercase tracking-tight text-on-surface">
                        Guscio Tecnico Pro-X</h3>
                    <span class="text-primary font-black text-lg">€189.00</span>
                </div>
                <code class="text-[10px] font-mono text-secondary mb-4">SKU: OP-JKT-09</code>
                <p class="text-sm text-secondary-container line-clamp-2 mb-6">Impermeabilità 20k,
                    traspirabilità estrema. Ideale per ambienti outdoor industriali.</p>
                <div class="mt-auto flex justify-between items-center">
                    <span
                        class="text-[10px] bg-secondary-container px-2 py-1 font-bold text-on-secondary-fixed-variant uppercase">EN
                        ISO 20471</span>
                    <button
                        class="material-symbols-outlined text-primary hover:scale-110 transition-transform">add_circle</button>
                </div>
            </div>
        </article>
        <!-- Product Card 2 -->
        <article
            class="group bg-surface-container-lowest flex flex-col h-full border-b-4 border-transparent hover:border-primary transition-all duration-300">
            <div class="aspect-[4/5] overflow-hidden bg-surface-container relative">
                <img class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-500 group-hover:scale-105"
                    data-alt="Premium navy corporate polo shirt layout"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuCdPXRiPmZkgtmttFDm3re0kfG_AV7BezQq2iI-MYV26B--evo3AUiu3tdqu9-Xz1PfZ9Hq8bm7uMVaqH5dMEMdUBc6n3H3bgAlnMo0uDoGS4WeWQVGILK_g88vPDVQIbLNSQUpH3Qvzgh44iKwPQWBPIofmuTFT6PtvB_o_ntO20q3UjjTsS9Emu2iyTa9suoTiLvTsb548vICiALTsHzgUXPIqWIBgnPmU_qg7npcPJ8ceaFxoLTb6T4B2GPLmyevg7ZO5LA7o8w" />
            </div>
            <div class="p-6 flex flex-col flex-1">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="text-lg font-bold leading-tight uppercase tracking-tight text-on-surface">
                        Polo Corporate Piqué</h3>
                    <span class="text-primary font-black text-lg">€34.50</span>
                </div>
                <code class="text-[10px] font-mono text-secondary mb-4">SKU: OP-POLO-BK</code>
                <p class="text-sm text-secondary-container line-clamp-2 mb-6">100% Cotone pettinato,
                    finiture rinforzate su colletto e maniche.</p>
                <div class="mt-auto flex justify-between items-center">
                    <span
                        class="text-[10px] bg-secondary-container px-2 py-1 font-bold text-on-secondary-fixed-variant uppercase">Oeko-Tex
                        100</span>
                    <button
                        class="material-symbols-outlined text-primary hover:scale-110 transition-transform">add_circle</button>
                </div>
            </div>
        </article>
        <!-- Product Card 3 -->
        <article
            class="group bg-surface-container-lowest flex flex-col h-full border-b-4 border-transparent hover:border-primary transition-all duration-300">
            <div class="aspect-[4/5] overflow-hidden bg-surface-container relative">
                <img class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-500 group-hover:scale-105"
                    data-alt="Industrial roll-up banner display mockup"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuDhqDdvdZPi8iiAP2e5kY0NQWMol0VTijtG3ZlrLv-BGH0xv5JuHf9fRBnvuznaxnaiFbYnwxH69DA5srB9uv1mnOqX62HCHbVjBGQygWgL7VSqMgU6DjpdjSLEN_wHcAkyw-MQgX2-KzkFJSQijgebdxWiV481E32BAfahKiOKRKiEEBuGptSGd5-4Zahqk7iN4FOmGmoJvG_7d24JezF3YLsFk3VQTnok__y9oKh5lrYR8QFE3V2ljx7YrfntQaaL9huHxcGCSY8" />
            </div>
            <div class="p-6 flex flex-col flex-1">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="text-lg font-bold leading-tight uppercase tracking-tight text-on-surface">
                        Roll-Up Industriale 85x200</h3>
                    <span class="text-primary font-black text-lg">€79.00</span>
                </div>
                <code class="text-[10px] font-mono text-secondary mb-4">SKU: OP-DISP-01</code>
                <p class="text-sm text-secondary-container line-clamp-2 mb-6">Struttura in alluminio
                    rinforzato, stampa UV su telo PVC blockout.</p>
                <div class="mt-auto flex justify-between items-center">
                    <span
                        class="text-[10px] bg-secondary-container px-2 py-1 font-bold text-on-secondary-fixed-variant uppercase">B1
                        Fire-Rated</span>
                    <button
                        class="material-symbols-outlined text-primary hover:scale-110 transition-transform">add_circle</button>
                </div>
            </div>
        </article>
        <!-- Product Card 4 -->
        <article
            class="group bg-surface-container-lowest flex flex-col h-full border-b-4 border-transparent hover:border-primary transition-all duration-300">
            <div class="aspect-[4/5] overflow-hidden bg-surface-container relative">
                <img class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-500 group-hover:scale-105"
                    data-alt="Premium business cards on thick textured paper"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuCdPXRiPmZkgtmttFDm3re0kfG_AV7BezQq2iI-MYV26B--evo3AUiu3tdqu9-Xz1PfZ9Hq8bm7uMVaqH5dMEMdUBc6n3H3bgAlnMo0uDoGS4WeWQVGILK_g88vPDVQIbLNSQUpH3Qvzgh44iKwPQWBPIofmuTFT6PtvB_o_ntO20q3UjjTsS9Emu2iyTa9suoTiLvTsb548vICiALTsHzgUXPIqWIBgnPmU_qg7npcPJ8ceaFxoLTb6T4B2GPLmyevg7ZO5LA7o8w" />
            </div>
            <div class="p-6 flex flex-col flex-1">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="text-lg font-bold leading-tight uppercase tracking-tight text-on-surface">
                        Biglietti Da Visita Premium</h3>
                    <span class="text-primary font-black text-lg">€45.00</span>
                </div>
                <code class="text-[10px] font-mono text-secondary mb-4">SKU: OP-CARD-300</code>
                <p class="text-sm text-secondary-container line-clamp-2 mb-6">Carta patinata opaca 350g,
                    plastificazione soft-touch fronte/retro.</p>
                <div class="mt-auto flex justify-between items-center">
                    <span
                        class="text-[10px] bg-secondary-container px-2 py-1 font-bold text-on-secondary-fixed-variant uppercase">FSC
                        Certified</span>
                    <button
                        class="material-symbols-outlined text-primary hover:scale-110 transition-transform">add_circle</button>
                </div>
            </div>
        </article>
        <!-- Product Card 5 -->
        <article
            class="group bg-surface-container-lowest flex flex-col h-full border-b-4 border-transparent hover:border-primary transition-all duration-300">
            <div class="aspect-[4/5] overflow-hidden bg-surface-container relative">
                <img class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-500 group-hover:scale-105"
                    data-alt="Professional cargo work trousers with pockets"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuCdPXRiPmZkgtmttFDm3re0kfG_AV7BezQq2iI-MYV26B--evo3AUiu3tdqu9-Xz1PfZ9Hq8bm7uMVaqH5dMEMdUBc6n3H3bgAlnMo0uDoGS4WeWQVGILK_g88vPDVQIbLNSQUpH3Qvzgh44iKwPQWBPIofmuTFT6PtvB_o_ntO20q3UjjTsS9Emu2iyTa9suoTiLvTsb548vICiALTsHzgUXPIqWIBgnPmU_qg7npcPJ8ceaFxoLTb6T4B2GPLmyevg7ZO5LA7o8w" />
            </div>
            <div class="p-6 flex flex-col flex-1">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="text-lg font-bold leading-tight uppercase tracking-tight text-on-surface">
                        Pantaloni Cargo Work-Pro</h3>
                    <span class="text-primary font-black text-lg">€62.00</span>
                </div>
                <code class="text-[10px] font-mono text-secondary mb-4">SKU: OP-TR-44</code>
                <p class="text-sm text-secondary-container line-clamp-2 mb-6">Tessuto stretch
                    anti-abrasione con tasche rinforzate porta-attrezzi.</p>
                <div class="mt-auto flex justify-between items-center">
                    <span
                        class="text-[10px] bg-secondary-container px-2 py-1 font-bold text-on-secondary-fixed-variant uppercase">EN
                        ISO 13688</span>
                    <button
                        class="material-symbols-outlined text-primary hover:scale-110 transition-transform">add_circle</button>
                </div>
            </div>
        </article>
        <!-- Product Card 6 -->
        <article
            class="group bg-surface-container-lowest flex flex-col h-full border-b-4 border-transparent hover:border-primary transition-all duration-300">
            <div class="aspect-[4/5] overflow-hidden bg-surface-container relative">
                <img class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-500 group-hover:scale-105"
                    data-alt="Reflective branding on industrial signage"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuCdPXRiPmZkgtmttFDm3re0kfG_AV7BezQq2iI-MYV26B--evo3AUiu3tdqu9-Xz1PfZ9Hq8bm7uMVaqH5dMEMdUBc6n3H3bgAlnMo0uDoGS4WeWQVGILK_g88vPDVQIbLNSQUpH3Qvzgh44iKwPQWBPIofmuTFT6PtvB_o_ntO20q3UjjTsS9Emu2iyTa9suoTiLvTsb548vICiALTsHzgUXPIqWIBgnPmU_qg7npcPJ8ceaFxoLTb6T4B2GPLmyevg7ZO5LA7o8w" />
            </div>
            <div class="p-6 flex flex-col flex-1">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="text-lg font-bold leading-tight uppercase tracking-tight text-on-surface">
                        Targa segnaletica riflettente</h3>
                    <span class="text-primary font-black text-lg">€28.00</span>
                </div>
                <code class="text-[10px] font-mono text-secondary mb-4">SKU: OP-SIGN-REF</code>
                <p class="text-sm text-secondary-container line-clamp-2 mb-6">Supporto rigido in Dibond,
                    pellicola riflettente Classe 1.</p>
                <div class="mt-auto flex justify-between items-center">
                    <span
                        class="text-[10px] bg-secondary-container px-2 py-1 font-bold text-on-secondary-fixed-variant uppercase">DIN
                        67510</span>
                    <button
                        class="material-symbols-outlined text-primary hover:scale-110 transition-transform">add_circle</button>
                </div>
            </div>
        </article>
    </div>
    <!-- Pagination (Architectural style) -->
    <div class="mt-16 flex items-center justify-center gap-2">
        <button
            class="w-10 h-10 border border-slate-200 flex items-center justify-center hover:bg-primary hover:text-white transition-colors">
            <span class="material-symbols-outlined text-sm">chevron_left</span>
        </button>
        <button class="w-10 h-10 bg-primary text-white font-bold text-xs">01</button>
        <button class="w-10 h-10 border border-slate-200 font-bold text-xs hover:bg-slate-50">02</button>
        <button class="w-10 h-10 border border-slate-200 font-bold text-xs hover:bg-slate-50">03</button>
        <span class="px-2 text-secondary">...</span>
        <button class="w-10 h-10 border border-slate-200 font-bold text-xs hover:bg-slate-50">12</button>
        <button
            class="w-10 h-10 border border-slate-200 flex items-center justify-center hover:bg-primary hover:text-white transition-colors">
            <span class="material-symbols-outlined text-sm">chevron_right</span>
        </button>
    </div>
    </div>

</x-layout>
