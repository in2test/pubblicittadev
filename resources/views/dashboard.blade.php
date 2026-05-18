<x-layouts::app :title="__('Dashboard')">
    <div class="space-y-8">
        <div>
            <h2 class="text-2xl font-black uppercase tracking-tight text-gray-950">Pannello Utente</h2>
            <p class="text-gray-500 text-sm mt-1">Benvenuto nella tua area personale, {{ auth()->user()->name }}.</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            {{-- Orders Card --}}
            <a href="{{ route('dashboard.orders') }}" 
               class="group relative overflow-hidden border-2 border-gray-950 p-6 bg-gray-50 hover:bg-gray-100 transition-colors shadow-md shadow-gray-950/5">
                <div class="flex items-center gap-4">
                    <div class="border-2 border-gray-950 bg-secondary text-gray-50 p-3 flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl font-bold">shopping_bag</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-wider text-gray-950">I Miei Ordini</h3>
                        <p class="text-xs text-gray-500 mt-1 font-medium">Visualizza gli ordini effettuati e il loro stato.</p>
                    </div>
                </div>
            </a>

            {{-- Quotes Card --}}
            <a href="{{ route('dashboard.quotes') }}" 
               class="group relative overflow-hidden border-2 border-gray-950 p-6 bg-gray-50 hover:bg-gray-100 transition-colors shadow-md shadow-gray-950/5">
                <div class="flex items-center gap-4">
                    <div class="border-2 border-gray-950 bg-secondary text-gray-50 p-3 flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl font-bold">request_quote</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-wider text-gray-950">I Miei Preventivi</h3>
                        <p class="text-xs text-gray-500 mt-1 font-medium">Visualizza le tue richieste di preventivo.</p>
                    </div>
                </div>
            </a>

            {{-- Addresses Card --}}
            <a href="{{ route('dashboard.addresses') }}" 
               class="group relative overflow-hidden border-2 border-gray-950 p-6 bg-gray-50 hover:bg-gray-100 transition-colors shadow-md shadow-gray-950/5">
                <div class="flex items-center gap-4">
                    <div class="border-2 border-gray-950 bg-secondary text-gray-50 p-3 flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl font-bold">location_on</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-wider text-gray-950">I Miei Indirizzi</h3>
                        <p class="text-xs text-gray-500 mt-1 font-medium">Gestisci gli indirizzi di spedizione e fatturazione.</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</x-layouts::app>
