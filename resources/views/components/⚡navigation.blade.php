    <?php

use Livewire\Component;
use App\Models\Category;

new class extends Component {
    public function mount(): void
    {
        $this->categories = Category::with('children')->get();
    }
};
?>

<nav class="mega-nav" data-mobile-open="false">
    <div class="mega-nav-shell">
        <div class="mega-nav-row">
            <ul class="mega-menu-root">
                <li class="mega-item" data-open="false">
                    <button class="mega-trigger" type="button" data-mega-trigger aria-expanded="false">
                        Catalogo per categoria
                        <svg class="mega-trigger-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div class="mega-panel">
                        <div class="mega-panel-grid">
                            @foreach ($this->categories->where('parent_id', null) as $category)
                                <div>
                                    <h3 class="mega-col-title">{{ $category->name }}</h3>
                                    <ul class="mega-link-list">
                                        @foreach ($category->children as $categoryChild)
                                            <li>
                                                <a href="{{ route('category', $categoryChild->slug) }}"
                                                    class="mega-link">
                                                    <span class="mega-link-title">{{ $categoryChild->name }}</span>
                                                    <span
                                                        class="mega-link-copy">{{ Str::limit($categoryChild->description, 60) }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </li>

                <!--li class="mega-item" data-open="false">
                    <button class="mega-trigger" type="button" data-mega-trigger aria-expanded="false">
                        Services
                        <svg class="mega-trigger-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div class="mega-panel">
                        <div class="mega-panel-grid">
                            <div>
                                <h3 class="mega-col-title">Design</h3>
                                <ul class="mega-link-list">
                                    <li><a href="#" class="mega-link"><span class="mega-link-title">Brand
                                                Systems</span><span class="mega-link-copy">Identity design for digital
                                                and print products.</span></a></li>
                                    <li><a href="#" class="mega-link"><span class="mega-link-title">UI
                                                Audits</span><span class="mega-link-copy">Review flows, hierarchy, and
                                                conversion friction.</span></a></li>
                                </ul>
                            </div>
                            <div>
                                <h3 class="mega-col-title">Development</h3>
                                <ul class="mega-link-list">
                                    <li><a href="#" class="mega-link"><span class="mega-link-title">Laravel
                                                Builds</span><span class="mega-link-copy">Custom applications, admin
                                                panels, and APIs.</span></a></li>
                                    <li><a href="#" class="mega-link"><span
                                                class="mega-link-title">E-commerce</span><span
                                                class="mega-link-copy">Catalogs, checkout flows, and
                                                integrations.</span></a></li>
                                </ul>
                            </div>
                            <div>
                                <h3 class="mega-col-title">Support</h3>
                                <ul class="mega-link-list">
                                    <li><a href="#" class="mega-link"><span
                                                class="mega-link-title">Maintenance</span><span
                                                class="mega-link-copy">Keep your platform secure and
                                                updated.</span></a></li>
                                    <li><a href="#" class="mega-link"><span
                                                class="mega-link-title">Consulting</span><span
                                                class="mega-link-copy">Architecture, migrations, and planning
                                                sessions.</span></a></li>
                                </ul>
                            </div>
                            <div class="mega-feature">
                                <p class="mega-feature-kicker">New</p>
                                <h3 class="mega-feature-title">Need a combined design and dev workflow?</h3>
                                <p class="mega-feature-copy">Use the feature block for a service spotlight, campaign,
                                    or callout with higher visual priority.</p>
                                <a href="#" class="mega-feature-cta">Book a call</a>
                            </div>
                        </div>
                    </div>
                </li-->

                <li><a href="{{ route('services') }}" class="mega-simple-link">Servizi</a></li>
                <li><a href="{{ route('contact') }}" class="mega-simple-link">Contatti</a></li>
                <li class="mega-item" data-open="false">
                    <button class="mega-trigger" type="button" data-mega-trigger aria-expanded="false">

                        <span class="material-symbols-outlined">search</span>

                        <svg class="mega-trigger-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div class="mega-panel py-2" id="search-menu-panel">
                        <form action="http://localhost:8000/search" method="GET" class="flex w-full items-center">

                            <input name="q" type="text" placeholder="Cerca prodotti, SKU..."
                                class="w-full text-sm bg-transparent border-none focus:ring-0 font-mono tracking-tight">
                            <!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]-->
                        </form>
                    </div>
                </li>
            </ul>
            <a href="/" class="mega-logo">PubbliCitta24</a>
            <div class="flex items-center gap-2 lg:gap-6">

                @php
                    $cartCount = \Illuminate\Support\Facades\Session::get('cart_items', []);
                    $count = is_array($cartCount) ? array_sum(array_column($cartCount, 'quantity')) : 0;
                    $user = Illuminate\Support\Facades\Auth::user();
                @endphp
                <a href="{{ route('cart') }}"
                    class="relative p-2 hover:bg-gray-100  text-gray-700 border-gray-50 hover:text-gray-950 focus:outline-none border-b-2 focus:border-gray-400 transition-colors">
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
                        <button id="user-menu-button" aria-label="Account menu" @click="open = !open"
                            @mouseenter="open = true"
                            class="p-2 hover:bg-gray-100  text-gray-700 border-gray-50 hover:text-gray-950 focus:outline-none border-b-2 focus:border-gray-400 transition-colors">
                            <span class="material-symbols-outlined text-green-600">verified_user</span>
                        </button>
                        <div id="user-dropdown" x-show="open" @click.away="open = false" x-transition.opacity
                            class="absolute right-0 top-full mt-1 w-48 rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 z-50">
                            <div class="px-4 py-2 border-b border-gray-100">
                                <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                            </div>
                            <a href="{{ route('dashboard') }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
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
                        class="p-2 hover:bg-gray-100  text-gray-700 border-gray-50 hover:text-gray-950 focus:outline-none border-b-2 focus:border-gray-400 transition-colors"
                        onclick="openAuthModal()">
                        <span class="material-symbols-outlined">account_circle</span>
                    </button>
                @endif


                <!-- Dark Mode Toggle Button -->
                <button id="theme-toggle" aria-label="Toggle dark mode"
                    class="p-2 hover:bg-gray-100  text-gray-700 border-gray-50 hover:text-gray-950 focus:outline-none border-b-2 focus:border-gray-400 transition-colors">
                    <div id="icon-system" class=""><span class="material-symbols-outlined">computer</span>
                    </div>
                    <div id="icon-light" class="hidden"><span class="material-symbols-outlined">light_mode</span>
                    </div>
                    <div id="icon-dark" class="hidden"><span class="material-symbols-outlined">dark_mode</span></div>
                </button>
                <!-- Mobile toggle button for small screens (re-added) -->
                <button type="button" data-mobile-toggle aria-expanded="false" aria-controls="mobile-menu"
                    class="p-2 hover:bg-gray-100  text-gray-700 border-gray-50 hover:text-gray-950 focus:outline-none border-b-2 focus:border-gray-400 transition-colors lg:hidden">
                    <span class="material-symbols-outlined">menu</span>
                </button>



            </div>



        </div>
    </div>

    <div class="mega-mobile-backdrop" data-mobile-backdrop></div>

    <div id="mobile-menu" class="mega-mobile-panel" aria-hidden="true">
        <div class="mega-mobile-header">
            <span class="mega-logo">PubbliCitta24</span>
            <button class="mega-mobile-close" type="button" data-mobile-close aria-label="Close menu">
                ✕
            </button>
        </div>
        @foreach ($this->categories->where('parent_id', null) as $category)
            <div class="mega-mobile-group" data-open="false">
                @if ($category->children->isEmpty())
                    <a href="{{ route('category', $category->slug) }}" class="mega-mobile-sublink">
                        {{ $category->name }}
                    </a>
                @else
                    <button class="mega-mobile-trigger" type="button" data-mobile-group-trigger>
                        {{ $category->name }}
                        <span>+</span>
                    </button>
                    <div class="mega-mobile-submenu">
                        @foreach ($category->children as $categoryChild)
                            <a href="{{ route('category', $categoryChild->slug) }}" class="mega-mobile-sublink">
                                {{ $categoryChild->name }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
        <div class="mega-mobile-group">
            <a href="{{ route('services') }}" class="mega-mobile-sublink">Servizi</a>
            <a href="{{ route('contact') }}" class="mega-mobile-sublink">Contatti</a>
        </div>
    </div>
</nav>
