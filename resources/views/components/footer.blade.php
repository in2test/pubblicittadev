<footer class="bg-gray-50 border-t-0">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-12 px-8 3xl:px-32 py-16 w-full mx-auto">
        <div>
            <div class="text-lg font-bold text-gray-900 mb-6 font-headline">PUBBLICITTÀ24
            </div>
            <p class="text-gray-700 font-mono text-[10px] leading-relaxed max-w-xs">
                Pubblicittà 24 srls<br />
                SS 155 per Fiuggi, 128<br />
                03010 Trivigliano (FR) - Italy
            </p>
            <div class="mt-4">
                <a href="https://www.google.com/maps/place/PubbliCitta'+24+srls/@41.7723226,13.269293,1417m/data=!3m2!1e3!4b1!4m6!3m5!1s0x132558ae53356aa1:0xd87cadd6464c2404!8m2!3d41.7723186!4d13.2718679!16s%2Fg%2F11b6jgf95v?entry=ttu&g_ep=EgoyMDI2MDUyNy4wIKXMDSoASAFQAw%3D%3D" target="_blank" class="inline-flex items-center gap-1 font-mono text-[10px] uppercase tracking-widest text-accent-500 hover:text-accent-700 transition-colors">
                    <span class="material-symbols-outlined text-[14px]">map</span> Vedi sulla Mappa
                </a>
            </div>
        </div>
        <div class="flex flex-col space-y-4">
            <span class="font-mono text-[10px] uppercase tracking-widest text-accent-500 mb-2">Esplora</span>
            <a class="font-mono text-xs uppercase tracking-widest text-gray-700 hover:text-accent-700 transition-colors"
                href="{{ route('about') }}">Chi Siamo</a>
            <a class="font-mono text-xs uppercase tracking-widest text-gray-700 hover:text-accent-700 transition-colors"
                href="{{ route('services') }}">Servizi</a>
            <a class="font-mono text-xs uppercase tracking-widest text-gray-700 hover:text-accent-700 transition-colors"
                href="{{ route('portfolio') }}">Portfolio Lavori</a>
            <a class="font-mono text-xs uppercase tracking-widest text-gray-700 hover:text-accent-700 transition-colors"
                href="{{ route('contact') }}">Supporto</a>
        </div>
        <div class="flex flex-col space-y-4">
            <span class="font-mono text-[10px] uppercase tracking-widest text-accent-500 mb-2">Lavorazioni & Prodotti</span>
            @foreach(\App\Models\Category::whereNull('parent_id')->where('is_active', true)->get() as $category)
                <a class="font-mono text-xs uppercase tracking-widest text-gray-700 hover:text-accent-700 transition-colors"
                    href="{{ route('category', $category->slug) }}">{{ $category->name }}</a>
            @endforeach
        </div>
        <div>
            <span class="font-mono text-[10px] uppercase tracking-widest text-accent-500 mb-6 block">Newsletter</span>
            <livewire:newsletter-form />
        </div>
    </div>
    <div
        class="px-12 py-8 border-t border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
        <span class="font-mono text-xs uppercase tracking-widest text-gray-700">© {{ date('Y') }} PUBBLICITTÀ24 srls</span>
        <div class="flex flex-wrap gap-x-8 gap-y-2 justify-center md:justify-end">
            <a class="font-mono text-[10px] text-accent-500 hover:text-accent-700 transition-colors uppercase tracking-widest"
                href="{{ route('terms') }}">Condizioni di Vendita</a>
            <a class="font-mono text-[10px] text-accent-500 hover:text-accent-700 transition-colors uppercase tracking-widest"
                href="{{ route('privacy') }}">Privacy Policy</a>
            <a class="font-mono text-[10px] text-accent-500 hover:text-accent-700 transition-colors uppercase tracking-widest"
                href="{{ route('cookie-policy') }}">Cookie Policy</a>
            <a class="font-mono text-[10px] text-accent-500 hover:text-accent-700 transition-colors uppercase tracking-widest"
                href="{{ route('shipping-returns') }}">Spedizioni e Resi</a>
        </div>
    </div>
</footer>