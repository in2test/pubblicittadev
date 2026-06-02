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
                <a href="https://maps.app.goo.gl/D2YXZVwvT2G7a" target="_blank" class="inline-flex items-center gap-1 font-mono text-[10px] uppercase tracking-widest text-accent-500 hover:text-accent-700 transition-colors">
                    <span class="material-symbols-outlined text-[14px]">map</span> Vedi sulla Mappa
                </a>
            </div>
        </div>
        <div class="flex flex-col space-y-4">
            <span class="font-mono text-[10px] uppercase tracking-widest text-accent-500 mb-2">Servizi</span>
            <a class="font-mono text-xs uppercase tracking-widest text-gray-700 hover:text-accent-700 transition-colors"
                href="{{ route('services') }}">Biglietti da visita</a>
            <a class="font-mono text-xs uppercase tracking-widest text-gray-700 hover:text-accent-700 transition-colors"
                href="{{ route('services') }}">Volantini e brochure</a>
            <a class="font-mono text-xs uppercase tracking-widest text-gray-700 hover:text-accent-700 transition-colors"
                href="{{ route('services') }}">T-shirt e abbigliamento</a>
            <a class="font-mono text-xs uppercase tracking-widest text-gray-700 hover:text-accent-700 transition-colors"
                href="{{ route('contact') }}">Supporto</a>
        </div>
        <div class="flex flex-col space-y-4">
            <span class="font-mono text-[10px] uppercase tracking-widest text-accent-500 mb-2">Servizi</span>
            <a class="font-mono text-xs uppercase tracking-widest text-gray-700 hover:text-accent-700 transition-colors"
                href="{{ route('services') }}">Ricamo Industriale</a>
            <a class="font-mono text-xs uppercase tracking-widest text-gray-700 hover:text-accent-700 transition-colors"
                href="{{ route('services') }}">Serigrafia</a>
            <a class="font-mono text-xs uppercase tracking-widest text-gray-700 hover:text-accent-700 transition-colors"
                href="{{ route('services') }}">Allestimenti</a>
            <a class="font-mono text-xs uppercase tracking-widest text-gray-700 hover:text-accent-700 transition-colors"
                href="{{ route('portfolio') }}">Portfolio Lavori</a>
        </div>
        <div>
            <span class="font-mono text-[10px] uppercase tracking-widest text-accent-500 mb-6 block">Newsletter</span>
            <livewire:newsletter-form />
        </div>
    </div>
    <div
        class="px-12 py-8 border-t border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
        <span class="font-mono text-xs uppercase tracking-widest text-gray-700">© {{ date('Y') }} PUBBLICITTÀ24 srls</span>
        <div class="flex gap-8">
            <a class="font-mono text-[10px] text-accent-500 hover:text-accent-700 transition-colors uppercase tracking-widest"
                href="{{ route('privacy') }}">Informativa Privacy</a>
            <a class="font-mono text-[10px] text-accent-500 hover:text-accent-700 transition-colors uppercase tracking-widest"
                href="{{ route('terms') }}">Termini e Condizioni</a>
        </div>
    </div>
</footer>