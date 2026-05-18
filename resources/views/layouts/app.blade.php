<x-layout :title="$title ?? 'Area Riservata'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Dashboard Title Header -->
        <div class="mb-10">
            <h1 class="text-3xl font-black uppercase tracking-tighter text-gray-950">Area Riservata</h1>
            <p class="text-gray-500 text-sm mt-1">Gestisci i tuoi ordini, preventivi, indirizzi e impostazioni del profilo.</p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Left Navigation Sidebar -->
            <div class="lg:col-span-3 space-y-4">
                <nav class="border-2 border-gray-950 bg-gray-50 p-2 flex flex-col gap-1 shadow-md shadow-gray-950/5">
                    <a href="{{ route('dashboard') }}" 
                       class="flex items-center gap-3 px-4 py-3 text-xs font-black uppercase tracking-wider border-2 transition-colors {{ request()->routeIs('dashboard') ? 'bg-secondary border-secondary text-gray-50' : 'bg-transparent border-transparent text-gray-900 hover:bg-gray-100 hover:text-gray-950' }}"
                       wire:navigate>
                        <span class="material-symbols-outlined text-lg">dashboard</span>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('dashboard.orders') }}" 
                       class="flex items-center gap-3 px-4 py-3 text-xs font-black uppercase tracking-wider border-2 transition-colors {{ request()->routeIs('dashboard.orders*') ? 'bg-secondary border-secondary text-gray-50' : 'bg-transparent border-transparent text-gray-900 hover:bg-gray-100 hover:text-gray-950' }}"
                       wire:navigate>
                        <span class="material-symbols-outlined text-lg">shopping_bag</span>
                        <span>I Miei Ordini</span>
                    </a>

                    <a href="{{ route('dashboard.quotes') }}" 
                       class="flex items-center gap-3 px-4 py-3 text-xs font-black uppercase tracking-wider border-2 transition-colors {{ request()->routeIs('dashboard.quotes*') ? 'bg-secondary border-secondary text-gray-50' : 'bg-transparent border-transparent text-gray-900 hover:bg-gray-100 hover:text-gray-950' }}"
                       wire:navigate>
                        <span class="material-symbols-outlined text-lg">request_quote</span>
                        <span>I Miei Preventivi</span>
                    </a>

                    <a href="{{ route('dashboard.addresses') }}" 
                       class="flex items-center gap-3 px-4 py-3 text-xs font-black uppercase tracking-wider border-2 transition-colors {{ request()->routeIs('dashboard.addresses*') ? 'bg-secondary border-secondary text-gray-50' : 'bg-transparent border-transparent text-gray-900 hover:bg-gray-100 hover:text-gray-950' }}"
                       wire:navigate>
                        <span class="material-symbols-outlined text-lg">location_on</span>
                        <span>Indirizzi</span>
                    </a>

                    <a href="{{ route('profile.edit') }}" 
                       class="flex items-center gap-3 px-4 py-3 text-xs font-black uppercase tracking-wider border-2 transition-colors {{ request()->routeIs('profile.edit*') || request()->routeIs('security.edit*') || request()->routeIs('appearance.edit*') ? 'bg-secondary border-secondary text-gray-50' : 'bg-transparent border-transparent text-gray-900 hover:bg-gray-100 hover:text-gray-950' }}"
                       wire:navigate>
                        <span class="material-symbols-outlined text-lg">settings</span>
                        <span>Impostazioni</span>
                    </a>

                    <div class="border-t-2 border-gray-200 my-2 pt-2">
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <button type="submit" 
                                    class="w-full flex items-center gap-3 px-4 py-3 text-xs font-black uppercase tracking-wider border-2 border-transparent text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors">
                                <span class="material-symbols-outlined text-lg">logout</span>
                                <span>Disconnetti</span>
                            </button>
                        </form>
                    </div>
                </nav>
                
                @if(auth()->user()?->isAdmin())
                    <div class="border-2 border-amber-500 bg-amber-50/50 p-4 shadow-md shadow-amber-500/5">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-amber-800 mb-2">Amministrazione</h3>
                        <a href="{{ url('/admin') }}" 
                           class="block w-full text-center py-2.5 px-3 bg-amber-500 hover:bg-amber-600 text-white text-xs font-black uppercase tracking-wider transition-colors">
                            Pannello Admin
                        </a>
                    </div>
                @endif
            </div>

            <!-- Right Content Area -->
            <div class="lg:col-span-9">
                <div class="border-2 border-gray-950 bg-gray-50 p-8 shadow-md shadow-gray-950/5">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</x-layout>
