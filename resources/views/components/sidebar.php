<!-- SideNavBar / Filters -->
<aside
    class="hidden lg:flex flex-col fixed left-0 top-20 h-[calc(100vh-5rem)] p-6 overflow-y-auto bg-slate-50 dark:bg-slate-950 w-72 border-r border-slate-200 dark:border-slate-800 z-40">
    <div class="mb-6">
        <h2 class="font-['Inter'] text-sm uppercase tracking-widest text-red-900 dark:text-red-500 font-bold">
            Filtri Tecnici</h2>
        <p class="text-[10px] text-secondary uppercase tracking-tighter">Configurazione Specifica</p>
    </div>
    <div class="space-y-8">
        <!-- Categories -->
        <div class="space-y-3">
            <div class="flex items-center gap-2 text-primary font-bold">
                <span class="material-symbols-outlined text-sm">category</span>
                <span class="text-xs uppercase tracking-widest">Categorie</span>
            </div>
            <div class="flex flex-col gap-2">
                <label
                    class="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300 cursor-pointer hover:translate-x-1 transition-transform">
                    <input checked="" class="w-4 h-4 rounded-none border-slate-300 text-primary focus:ring-primary"
                        type="checkbox" />
                    Abbigliamento
                </label>
                <label
                    class="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300 cursor-pointer hover:translate-x-1 transition-transform">
                    <input class="w-4 h-4 rounded-none border-slate-300 text-primary focus:ring-primary"
                        type="checkbox" />
                    Stampa
                </label>
                <label
                    class="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300 cursor-pointer hover:translate-x-1 transition-transform">
                    <input class="w-4 h-4 rounded-none border-slate-300 text-primary focus:ring-primary"
                        type="checkbox" />
                    Branding
                </label>
            </div>
        </div>
        <div class="bg-slate-200 dark:bg-slate-800 h-px"></div>
        <!-- Materials -->
        <div class="space-y-3">
            <div class="flex items-center gap-2 text-primary font-bold">
                <span class="material-symbols-outlined text-sm">architecture</span>
                <span class="text-xs uppercase tracking-widest">Materiali</span>
            </div>
            <div class="flex flex-col gap-2">
                <label
                    class="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300 cursor-pointer hover:translate-x-1 transition-transform">
                    <input class="w-4 h-4 border-slate-300 text-primary focus:ring-primary" name="mat" type="radio" />
                    Cotone Tecnico
                </label>
                <label
                    class="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300 cursor-pointer hover:translate-x-1 transition-transform">
                    <input class="w-4 h-4 border-slate-300 text-primary focus:ring-primary" name="mat" type="radio" />
                    PVC Industriale
                </label>
                <label
                    class="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300 cursor-pointer hover:translate-x-1 transition-transform">
                    <input class="w-4 h-4 border-slate-300 text-primary focus:ring-primary" name="mat" type="radio" />
                    Carta 300g
                </label>
            </div>
        </div>
        <div class="bg-slate-200 dark:bg-slate-800 h-px"></div>
        <!-- Certifications -->
        <div class="space-y-3">
            <div class="flex items-center gap-2 text-primary font-bold">
                <span class="material-symbols-outlined text-sm">verified</span>
                <span class="text-xs uppercase tracking-widest">Certificazioni ISO/EN</span>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div
                    class="bg-secondary-container text-on-secondary-fixed-variant text-[10px] py-1 px-2 text-center font-bold">
                    ISO 9001</div>
                <div
                    class="bg-secondary-container text-on-secondary-fixed-variant text-[10px] py-1 px-2 text-center font-bold">
                    EN 471</div>
                <div
                    class="bg-secondary-container text-on-secondary-fixed-variant text-[10px] py-1 px-2 text-center font-bold">
                    ISO 14001</div>
            </div>
        </div>
    </div>
    <div class="mt-auto pt-8">
        <button
            class="w-full bg-red-900 text-white font-bold px-4 py-3 rounded-sm uppercase text-xs tracking-widest hover:bg-primary-container transition-colors active:scale-95">
            Applica Filtri
        </button>
    </div>
</aside>
