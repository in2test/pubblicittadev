<x-layouts::app :title="__('Dashboard')">
    @if(auth()->user()?->isAdmin())
        <div class="mb-4">
            <a href="{{ url('/admin') }}" class="rounded-lg bg-amber-500 px-4 py-2 text-white hover:bg-amber-600">
                Pannello Admin
            </a>
        </div>
    @endif
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {{-- Quotes Card --}}
            <a href="{{ route('dashboard.quotes') }}" class="group relative overflow-hidden rounded-xl border border-neutral-200 p-6 transition-all hover:border-amber-500 hover:shadow-lg dark:border-neutral-700">
                <div class="flex items-center gap-4">
                    <div class="rounded-lg bg-amber-100 p-3 text-amber-600 dark:bg-amber-900/30">
                        <span class="material-symbols-outlined text-3xl">request_quote</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold">I miei Preventivi</h3>
                        <p class="text-sm text-neutral-500">Visualizza la cronologia delle tue richieste.</p>
                    </div>
                </div>
            </a>

            {{-- Addresses Card --}}
            <a href="{{ route('dashboard.addresses') }}" class="group relative overflow-hidden rounded-xl border border-neutral-200 p-6 transition-all hover:border-amber-500 hover:shadow-lg dark:border-neutral-700">
                <div class="flex items-center gap-4">
                    <div class="rounded-lg bg-amber-100 p-3 text-amber-600 dark:bg-amber-900/30">
                        <span class="material-symbols-outlined text-3xl">location_on</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold">Indirizzi</h3>
                        <p class="text-sm text-neutral-500">Gestisci i tuoi indirizzi di spedizione.</p>
                    </div>
                </div>
            </a>

            {{-- Profile Card (Coming soon) --}}
            <div class="group relative overflow-hidden rounded-xl border border-neutral-200 p-6 dark:border-neutral-700 opacity-60">
                <div class="flex items-center gap-4">
                    <div class="rounded-lg bg-neutral-100 p-3 text-neutral-600 dark:bg-neutral-800">
                        <span class="material-symbols-outlined text-3xl">person</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold">Profilo</h3>
                        <p class="text-sm text-neutral-500">Gestisci i tuoi dati personali.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>
