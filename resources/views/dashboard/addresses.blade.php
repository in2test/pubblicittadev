<x-layouts::app :title="__('I miei Indirizzi')">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold uppercase tracking-tight">I miei Indirizzi</h1>
            <p class="text-neutral-500">Gestisci i tuoi indirizzi di spedizione e fatturazione.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 text-sm font-medium text-neutral-500 hover:text-gray-900">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Torna alla Dashboard
        </a>
    </div>

    <livewire:address-manager />
</x-layouts::app>
