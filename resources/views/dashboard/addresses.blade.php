<x-layouts::app :title="__('I miei Indirizzi')">
    <div class="mb-8">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-950 mb-4 transition-colors">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            <span>Torna alla Dashboard</span>
        </a>
        <h1 class="text-2xl font-bold uppercase tracking-tight text-gray-950">I miei Indirizzi</h1>
        <p class="text-gray-500 text-sm mt-1">Gestisci i tuoi indirizzi di spedizione e fatturazione.</p>
    </div>

    <livewire:address-manager />
</x-layouts::app>
