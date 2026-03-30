<!-- TopNavBar -->
<nav
    class="fixed top-0 w-full z-50 border-b-0 bg-white/80 dark:bg-zinc-950/80 backdrop-blur-md shadow-sm dark:shadow-none transition-all duration-300">
    <div class="flex justify-between items-center px-8 h-20 w-full mx-auto">
        <div class="text-2xl font-black tracking-tighter text-zinc-900 dark:text-white font-headline"><a
                href={{ route('home') }}>PubbliCittà 24</a>
        </div>
        <div class="hidden lg:flex items-center space-x-10">
            <a class="font-inter tracking-tight font-bold uppercase text-red-700 dark:text-red-500 border-b-2 border-red-700 pb-1"
                href={{ route('category', ['category' => 'abbigliamento_da_lavoro']) }}>Categorie</a>
            <a class="font-inter tracking-tight font-bold uppercase text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors"
                href={{ route('services') }}>Servizi</a>
            <a class="font-inter tracking-tight font-bold uppercase text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors"
                href={{ route('contact') }}>Contatti</a>
            <a class="font-inter tracking-tight font-bold uppercase text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors"
                href={{ route('cart') }}>Carrello</a>
        </div>
        <div class="flex items-center space-x-6">
            <button
                class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-sm transition-all active:scale-95 duration-150">
                <span class="material-symbols-outlined">shopping_cart</span>
            </button>
            <button
                class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-sm transition-all active:scale-95 duration-150">
                <span class="material-symbols-outlined">account_circle</span>
            </button>
            <button
                class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-sm transition-all active:scale-95 duration-150">
                <span class="material-symbols-outlined">computer</span>
            </button>
            <button class="lg:hidden p-2">
                <span class="material-symbols-outlined">menu</span>
            </button>
        </div>
    </div>
</nav>
