<!-- TopNavBar -->
<nav
    class="fixed top-0 w-full z-50 border-b border-gray-500 bg-gray-200/80 backdrop-blur-md shadow-sm transition-all duration-300">
    <div class="flex justify-between items-center px-8 3xl:px-32 h-20 w-full mx-auto">
        <div class="text-xl xl:text-2xl font-black tracking-tighter text-zinc-900 dark:text-white font-headline"><a
                href={{ route('home') }}>PubbliCittà 24</a>
        </div>

        <!-- Script moved to app.js -->
        <div id="mobile-menu-panel"
            class="hidden flex fixed inset-0 h-screen w-screen z-60 bg-vividauburn-200  space-x-8 text-lg flex-col justify-around items-end xl:hidden ">
            <a class="font-inter tracking-tight font-bold uppercase {{ request()->routeIs('services') ? 'text-gray-900  border-red-700' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 border-red-700/0 hover:border-red-700' }} border-b-2 pb-2.5 py-2 transition-colors"
                href={{ route('services') }}>Chi Siamo</a>
            <a class="font-inter tracking-tight font-bold uppercase {{ request()->routeIs('cart') ? 'text-gray-900  border-red-700' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 border-red-700/0 hover:border-red-700' }} border-b-2 pb-2.5 py-2 transition-colors"
                href={{ route('cart') }}>Servizi</a>
            <a class="font-inter tracking-tight font-bold uppercase {{ request()->routeIs('contact') ? 'text-gray-900  border-red-700' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 border-red-700/0 hover:border-red-700' }} border-b-2 pb-2.5 py-2 transition-colors"
                href={{ route('contact') }}>Contatti</a>
            <a class="font-inter tracking-tight font-bold uppercase {{ request()->routeIs('categories') ? 'text-gray-900  border-red-700' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 border-red-700/0 hover:border-red-700' }} border-b-2 pb-2.5 py-2 transition-colors"
                href={{ route('catalog') }}>Cataloghi Sfogliabili</a>
            <a class="font-inter tracking-tight font-bold uppercase {{ request()->routeIs('category') ? 'bg-gray-900 text-white' : 'bg-accent text-white hover:text-red-700 hover:bg-zinc-300' }} transition-colors px-8 py-2 dark:text-red-500 border-b-2 border-red-700/0 hover:border-red-700 pb-2.5"
                href={{ route('catalog') }}>Catalogo</a>
        </div>
        <div id="large-menu-panel" class="xl:flex hidden gap-10 items-center">
            <a class="font-inter tracking-tight font-bold uppercase {{ request()->routeIs('services') ? 'text-gray-900 dark:text-zinc-100 border-red-700' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 border-red-700/0 hover:border-red-700' }} border-b-2 pb-2.5 py-2 transition-colors"
                href={{ route('services') }}>Chi Siamo</a>
            <a class="font-inter tracking-tight font-bold uppercase {{ request()->routeIs('cart') ? 'text-gray-900 dark:text-zinc-100 border-red-700' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 border-red-700/0 hover:border-red-700' }} border-b-2 pb-2.5 py-2 transition-colors"
                href={{ route('cart') }}>Servizi</a>
            <a class="font-inter tracking-tight font-bold uppercase {{ request()->routeIs('contact') ? 'text-gray-900 dark:text-zinc-100 border-red-700' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 border-red-700/0 hover:border-red-700' }} border-b-2 pb-2.5 py-2 transition-colors"
                href={{ route('contact') }}>Contatti</a>
            <a class="font-inter tracking-tight font-bold uppercase {{ request()->routeIs('categories') ? 'text-gray-900 dark:text-zinc-100 border-red-700' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 border-red-700/0 hover:border-red-700' }} border-b-2 pb-2.5 py-2 transition-colors"
                href={{ route('catalog') }}>Cataloghi Sfogliabili</a>
            <a class="font-inter tracking-tight font-bold uppercase {{ request()->routeIs('category') ? 'bg-gray-900 text-white' : 'bg-accent text-white hover:text-red-700 hover:bg-zinc-300' }} transition-colors px-8 py-2 dark:text-red-500 border-b-2 border-red-700/0 hover:border-red-700 pb-2.5"
                href={{ route('catalog') }}>Catalogo</a>
        </div>
        <div class="flex items-center gap-2 lg:gap-6">
            @php
                $cartCount = \Illuminate\Support\Facades\Session::get('cart_items', []);
                $count = is_array($cartCount) ? array_sum(array_column($cartCount, 'quantity')) : 0;
                $user = Illuminate\Support\Facades\Auth::user();
            @endphp
            <a href="{{ route('cart') }}"
                class="relative p-2 hover:bg-gray-100  rounded-sm transition-all active:scale-95 duration-150">
                <span class="material-symbols-outlined">shopping_cart</span>
                @if ($count > 0)
                    <span
                        class="absolute -top-1 -right-1 bg-primary text-white text-xs w-5 h-5 flex items-center justify-center rounded-full font-bold">
                        {{ $count }}
                    </span>
                @endif
            </a>

@if($user)
                <div class="relative" x-data="{ open: false }">
                    <button id="user-menu-button" aria-label="Account menu" @click="open = !open" @mouseenter="open = true"
                        class="p-2 hover:bg-gray-100 rounded-sm transition-all active:scale-95 duration-150">
                        <span class="material-symbols-outlined text-green-600">verified_user</span>
                    </button>
                    <div id="user-dropdown" x-show="open" @click.away="open = false" x-transition.opacity
                        class="absolute right-0 top-full mt-1 w-48 rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 z-50">
                        <div class="px-4 py-2 border-b border-gray-100">
                            <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                        </div>
                        <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Dashboard
                        </a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            I tuoi ordini
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="block">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                Esci
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <button id="account-button" aria-label="Account"
                    class="p-2 hover:bg-gray-100  rounded-sm transition-all active:scale-95 duration-150"
                    onclick="openAuthModal()">
                    <span class="material-symbols-outlined">account_circle</span>
                </button>
            @endif


            <!-- Dark Mode Toggle Button -->
            <button id="theme-toggle" aria-label="Toggle dark mode"
                class="p-2 hover:bg-gray-100  rounded-sm transition-all active:scale-95 duration-150">
                <div id="icon-system" class=""><span class="material-symbols-outlined">computer</span></div>
                <div id="icon-light" class="hidden"><span class="material-symbols-outlined">light_mode</span></div>
                <div id="icon-dark" class="hidden"><span class="material-symbols-outlined">dark_mode</span></div>
            </button>
            <!-- Mobile toggle button for small screens (re-added) -->
            <button id="mobile-menu-toggle" aria-label="Open menu" aria-controls="mobile-menu-panel"
                aria-expanded="false"
                class="p-2 hover:bg-vividauburn-50  rounded-sm transition-all active:scale-95 duration-150 xl:hidden">
                <span class="material-symbols-outlined">menu</span>
            </button>

        </div>
    </div>
</nav>
