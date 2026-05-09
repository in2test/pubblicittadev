<header
    class="flex justify-between bg-highstyle-50 text-gray-900 font-body items-center px-4 md:px-10 xl:px-16 3xl:px-32 py-7 top-0 left-0 right-0 z-20 shadow-lg fixed">
    <nav id="mobile-menu-panel"
        class="
        bg-highstyle-50 shadow-lg lg:shadow-none
        
        hidden absolute z-50
        top-0 left-0 w-[calc(100%-3.5rem)] md:w-[calc(100%-5rem)] h-screen
        lg:block lg:static lg:z-0 
        lg:h-fit lg:w-fit
        
        ">
        <ul class="flex gap-6 lg:gap-6 lg:flex-row lg:px-0 lg:my-0 lg:text-base flex-col pl-10 my-8 text-xl">
            <li><a href="{{ route('catalog') }}"
                    class="transition-color duration-300 font-bold hover:border-b-vividauburn-800 border-b-2 border-b-gray-50 py-2 w-100% block">Categorie</a>

            </li>
            <li class="group ">
                <a href="{{ route('services') }}"
                    class=" transition-color duration-300 font-bold hover:border-b-vividauburn-800 border-b-2 border-b-gray-50 py-2 w-100% block">
                    <span
                        class="material-symbols-outlined group-hover:rotate-90 transition-transform duration-300">chevron_right</span>
                    Servizi</a>
                <div
                    class="
                            lg:absolute lg:w-screen lg:left-0 lg:top-18 lg:bg-gray-50
                            lg:px-10 xl:px-16 3xl:px-32 lg:ml-0 lg:flex-row
                            ml-8 pt-4 flex-col gap-4
                            lg:shadow-lg lg:gap-32
                            hidden
                            group-hover:flex
                             font-bold">
                    <div class="group/submenu">
                        <p>Abbigliamento da lavoro</p>
                        <ul class="font-mono font-normal py-4  flex-col gap-4 hidden group-hover/submenu:flex lg:flex">
                            <li>menu1</li>
                            <li>menu2</li>
                            <li>menu3</li>
                            <li>menu4</li>
                            <li>menu5</li>
                            <li>menu6</li>
                            <li>menu7</li>
                            <li>menu8</li>
                            <li>menu9</li>
                            <li>menu10</li>
                        </ul>
                    </div>
                    <div class="group/submenu">
                        <p>Abbigliamento da lavoro</p>
                        <ul class="font-mono font-normal py-4  flex-col gap-4 hidden group-hover/submenu:flex lg:flex">
                            <li>menu1</li>
                            <li>menu2</li>
                            <li>menu3</li>
                            <li>menu4</li>
                            <li>menu5</li>
                            <li>menu6</li>
                            <li>menu7</li>
                            <li>menu8</li>
                            <li>menu9</li>
                            <li>menu10</li>
                        </ul>
                    </div>

                </div>

            </li>
            <li class="group"><a href="{{ route('services') }}"
                    class=" transition-color duration-300 font-bold hover:border-b-vividauburn-800 border-b-2 border-b-gray-50 py-2 w-100% block">
                    <span
                        class="material-symbols-outlined group-hover:rotate-90 transition-transform duration-300">chevron_right</span>
                    Chi
                    siamo</a>
                <ul
                    class="lg:absolute lg:w-screen lg:left-0 lg:top-18 lg:bg-gray-50 lg:px-4 xl:px-16 3xl:px-32 pt-4 lg:shadow-lg hidden group-hover:flex ml-8 lg:ml-0 flex-col gap-4">
                    <li>menu1</li>
                    <li>menu2</li>
                    <li>menu3</li>
                    <li>menu4</li>
                    <li>menu5</li>
                    <li>menu6</li>
                    <li>menu7</li>
                    <li>menu8</li>
                    <li>menu9</li>
                    <li>menu10</li>
                </ul>
            </li>
            <li class="group"><a href="{{ route('contact') }}"
                    class=" transition-color duration-300 font-bold hover:border-b-vividauburn-800 border-b-2 border-b-gray-50 py-2 w-100% block">
                    <span
                        class="material-symbols-outlined group-hover:rotate-90 transition-transform duration-300">chevron_right</span>
                    Contatti</a>
                <ul
                    class="lg:absolute lg:w-screen lg:left-0 lg:top-18 lg:bg-gray-50 lg:px-4 xl:px-16 3xl:px-32 pt-4 lg:shadow-lg hidden group-hover:flex ml-8 lg:ml-0 flex-col gap-4">
                    <li>menu1</li>
                    <li>menu2</li>
                    <li>menu3</li>
                    <li>menu4</li>
                    <li>menu5</li>
                    <li>menu6</li>
                    <li>menu7</li>
                    <li>menu8</li>
                    <li>menu9</li>
                    <li>menu10</li>
                </ul>
            </li>
        </ul>
    </nav>

    <p><a href="{{ route('home') }}" class="text-xl font-bold text-vividauburn-500">PubbliCitta24</a></p>

    <div class="flex justify-between items-center gap-4 lg:gap-6">
        <a href="{{ route('search') }}"
            class="hover:bg-gray-100  rounded-sm transition-all active:scale-95 duration-150 hidden lg:block">
            <span class="material-symbols-outlined">search</span>
        </a>
        @php
            $cartCount = \Illuminate\Support\Facades\Session::get('cart_items', []);
            $count = is_array($cartCount) ? array_sum(array_column($cartCount, 'quantity')) : 0;
            $user = Illuminate\Support\Facades\Auth::user();
        @endphp
        <a href="{{ route('cart') }}"
            class="relative hover:bg-gray-100  rounded-sm transition-all active:scale-95 duration-150">
            <span class="material-symbols-outlined">shopping_cart</span>
            @if ($count > 0)
                <span
                    class="absolute -top-1 -right-1 bg-primary text-white text-xs w-5 h-5 flex items-center justify-center rounded-full font-bold">
                    {{ $count }}
                </span>
            @endif
        </a>

        @if ($user)
            <div class="relative" x-data="{ open: false }">
                <button id="user-menu-button" aria-label="Account menu" @click="open = !open" @mouseenter="open = true"
                    class="hover:bg-gray-100 rounded-sm transition-all active:scale-95 duration-150">
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
                        <button type="submit"
                            class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                            Esci
                        </button>
                    </form>
                </div>
            </div>
        @else
            <button id="account-button" aria-label="Account"
                class="hover:bg-gray-100  rounded-sm transition-all active:scale-95 duration-150"
                onclick="openAuthModal()">
                <span class="material-symbols-outlined">account_circle</span>
            </button>
        @endif


        <!-- Dark Mode Toggle Button -->
        <button id="theme-toggle" aria-label="Toggle dark mode"
            class="hover:bg-gray-100  rounded-sm transition-all active:scale-95 duration-150">
            <div id="icon-system" class=""><span class="material-symbols-outlined">computer</span></div>
            <div id="icon-light" class="hidden"><span class="material-symbols-outlined">light_mode</span></div>
            <div id="icon-dark" class="hidden"><span class="material-symbols-outlined">dark_mode</span></div>
        </button>
        <!-- Mobile toggle button for small screens (re-added) -->
        <button id="mobile-menu-toggle" aria-label="Open menu" aria-controls="mobile-menu-panel" aria-expanded="false"
            class="hover:bg-gray-100  rounded-sm transition-all active:scale-95 duration-150 lg:hidden">
            <span class="material-symbols-outlined">menu</span>
        </button>

    </div>
</header>
