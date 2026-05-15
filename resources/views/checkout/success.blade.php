<x-layout>
    <div class="max-w-2xl mx-auto py-16 text-center">
        <div class="mb-8">
            <span class="material-symbols-outlined text-7xl text-green-500">check_circle</span>
        </div>
        <h1 class="text-4xl font-black uppercase tracking-tighter mb-4">Pagamento Riuscito!</h1>
        <p class="text-xl text-secondary mb-8">Grazie per il tuo ordine. Stiamo elaborando la tua richiesta e riceverai una conferma via email a breve.</p>
        
        <div class="bg-surface-container p-6 rounded-lg mb-8 inline-block">
            <p class="font-mono text-sm uppercase text-secondary">ID Sessione: <span class="text-primary">{{ request('session_id') }}</span></p>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('dashboard.quotes') }}" class="bg-primary text-white px-8 py-4 font-bold uppercase tracking-widest hover:bg-primary-container transition-colors">
                I tuoi ordini
            </a>
            <a href="{{ route('catalog') }}" class="bg-secondary text-white px-8 py-4 font-bold uppercase tracking-widest hover:bg-black transition-colors">
                Torna allo shopping
            </a>
        </div>
    </div>
</x-layout>
