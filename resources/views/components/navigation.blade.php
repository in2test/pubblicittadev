<!-- TopNavBar -->
<nav
    class="fixed top-0 w-full z-50 border-b border-gray-500bg-gray-200/80 backdrop-blur-md shadow-sm transition-all duration-300">
    <div class="flex justify-between items-center px-8 3xl:px-32 h-20 w-full mx-auto">
        <div class="text-xl xl:text-2xl font-black tracking-tighter text-zinc-900 dark:text-white font-headline"><a
                href={{ route('home') }}>PubbliCittà 24</a>
        </div>
        <div class="hidden lg:flex items-center space-x-8 lg:text-xs xl:text-sm 2xl:text-md 3xl:text-lg">
            <a class="font-inter tracking-tight font-bold uppercase {{ request()->routeIs('services') ? 'text-zinc-900 dark:text-zinc-100 border-red-700' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 border-red-700/0 hover:border-red-700' }} border-b-2 pb-2.5 py-2 transition-colors"
                href={{ route('services') }}>Chi Siamo</a>
            <a class="font-inter tracking-tight font-bold uppercase {{ request()->routeIs('cart') ? 'text-zinc-900 dark:text-zinc-100 border-red-700' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 border-red-700/0 hover:border-red-700' }} border-b-2 pb-2.5 py-2 transition-colors"
                href={{ route('cart') }}>Servizi</a>
            <a class="font-inter tracking-tight font-bold uppercase {{ request()->routeIs('contact') ? 'text-zinc-900 dark:text-zinc-100 border-red-700' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 border-red-700/0 hover:border-red-700' }} border-b-2 pb-2.5 py-2 transition-colors"
                href={{ route('contact') }}>Contatti</a>
            <a class="font-inter tracking-tight font-bold uppercase {{ request()->routeIs('categories') ? 'text-zinc-900 dark:text-zinc-100 border-red-700' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 border-red-700/0 hover:border-red-700' }} border-b-2 pb-2.5 py-2 transition-colors"
                href={{ route('catalog') }}>Cataloghi Sfogliabili</a>
            <a class="font-inter tracking-tight font-bold uppercase {{ request()->routeIs('category') ? 'bg-zinc-900 text-white' : 'bg-accent text-white hover:text-red-700 hover:bg-zinc-300' }} transition-colors px-8 py-2 dark:text-red-500 border-b-2 border-red-700/0 hover:border-red-700 pb-2.5"
                href={{ route('catalog') }}>Catalogo</a>
        </div>
        <div class="flex items-center gap-2 lg:gap-6">
            @php
                $cartCount = \Illuminate\Support\Facades\Session::get('cart_items', []);
                $count = is_array($cartCount) ? array_sum(array_column($cartCount, 'quantity')) : 0;
            @endphp
            <a href="{{ route('cart') }}" class="relative p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-sm transition-all active:scale-95 duration-150">
                <span class="material-symbols-outlined">shopping_cart</span>
                @if($count > 0)
                    <span class="absolute -top-1 -right-1 bg-primary text-white text-xs w-5 h-5 flex items-center justify-center rounded-full font-bold">
                        {{ $count }}
                    </span>
                @endif
            </a>
            <button
                class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-sm transition-all active:scale-95 duration-150">
                <span class="material-symbols-outlined">account_circle</span>
            </button>

            <!-- Dark Mode Toggle Button -->
            <button id="theme-toggle" aria-label="Toggle dark mode"
                class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-sm transition-all active:scale-95 duration-150">
                <div id="icon-system" class=""><span class="material-symbols-outlined">computer</span></div>
                <div id="icon-light" class="hidden"><span class="material-symbols-outlined">light_mode</span></div>
                <div id="icon-dark" class="hidden"><span class="material-symbols-outlined">dark_mode</span></div>
            </button>
            <button class="lg:hidden p-2">
                <span class="material-symbols-outlined">menu</span>
            </button>
        </div>
    </div>
</nav>