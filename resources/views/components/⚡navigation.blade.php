<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\Category;
use App\Models\Product;
use App\Services\CartManager;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public string $searchQuery = '';

    #[Computed]
    public function cartCount(): int
    {
        return app(CartManager::class)->count();
    }

    #[Computed]
    public function authUser(): ?\App\Models\User
    {
        return Auth::user();
    }

    #[Computed]
    public function categories()
    {
        return Category::with('children')->get();
    }

    #[Computed]
    public function searchResults()
    {
        $queryStr = trim($this->searchQuery);
        if ($queryStr === '' || $queryStr === '0') {
            return collect();
        }

        return Product::search($queryStr)
            ->query(fn ($query) => $query->active()->with(['category'])->orderBy('name', 'asc'))
            ->take(5)
            ->get();
    }
};
?>

<nav x-data="{ megaMenuOpen: false, searchOpen: false, mobileMenuOpen: false }"
    class="mega-nav sticky z-40 top-0 left-0 w-full font-mono uppercase tracking-widest text-[11px]"
    data-mobile-open="false">
    <div class="mega-nav-shell">
        <div class="mega-nav-row">
            <!-- Left Section: Navigation links -->
            <ul class="mega-menu-root">
                <!-- Dropdown item for Catalog -->
                <li class="mega-item" @click.away="megaMenuOpen = false">
                    <button class="mega-trigger" type="button" @click="megaMenuOpen = !megaMenuOpen; searchOpen = false" :class="megaMenuOpen ? 'border-b-accent-500 text-gray-950' : ''">
                        CATALOGO
                        <span class="mega-trigger-icon material-symbols-outlined transition-transform duration-300" :class="megaMenuOpen ? 'rotate-180 text-accent-500' : ''">expand_more</span>
                    </button>

                    <!-- Mega dropdown panel for categories -->
                    <div class="mega-panel" :class="megaMenuOpen ? 'visible opacity-100 mt-0' : ''">
                        <div class="mega-panel-grid">
                            @foreach ($this->categories->where('parent_id', null) as $category)
                            <div>
                                <div class="mega-col-title border-b border-gray-200 pb-2 text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 mb-4">{{ mb_strtoupper($category->name) }}</div>
                                <ul class="mega-link-list space-y-2">
                                    @foreach ($category->children as $categoryChild)
                                    <li>
                                        <a href="{{ route('category', $categoryChild->slug) }}"
                                            class="mega-link block hover:text-accent-500 transition-colors py-1">
                                            <span class="mega-link-title block font-semibold text-gray-900 uppercase">{{ mb_strtoupper($categoryChild->name) }}</span>
                                            <span
                                                class="mega-link-copy block text-[10px] leading-relaxed text-gray-400 font-sans normal-case tracking-normal mt-0.5">{{ Str::limit($categoryChild->description ?? '', 60) }}</span>
                                        </a>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </li>

                <li><a href="{{ route('portfolio') }}" class="mega-simple-link">PORTFOLIO</a></li>
                <li><a href="{{ route('about') }}" class="mega-simple-link">CHI SIAMO</a></li>
                <li><a href="{{ route('services') }}" class="mega-simple-link">SERVIZI</a></li>
                <li><a href="{{ route('contact') }}" class="mega-simple-link">CONTATTI</a></li>
            </ul>

            <!-- Center Section: Centered Logo -->
            <a href="/" class="mega-logo">PUBBLICITTA24</a>

            <!-- Right Section: Actions/Icons -->
            <div class="flex items-center lg:gap-4 justify-self-end">
                <!-- Search trigger button -->
                <button id="search-trigger" aria-label="Cerca"
                    class="p-2 hover:bg-gray-100 text-gray-700 hover:text-gray-950 focus:outline-none transition-colors rounded-full"
                    @click="searchOpen = true; megaMenuOpen = false">
                    <span class="material-symbols-outlined">search</span>
                </button>

                <!-- Cart button -->
                <a href="{{ route('cart') }}"
                    class="relative p-2 hover:bg-gray-100 text-gray-700 hover:text-gray-950 focus:outline-none transition-colors rounded-full">
                    <span class="material-symbols-outlined">shopping_cart</span>
                    @if ($this->cartCount > 0)
                    <span
                        class="absolute -top-1 -right-1 bg-accent-500 text-gray-50 text-[10px] w-4.5 h-4.5 flex items-center justify-center rounded-full font-bold">
                        {{ $this->cartCount }}
                    </span>
                    @endif
                </a>

                <!-- Account dropdown / trigger button -->
                @if ($this->authUser)
                <div class="relative hidden lg:block" x-data="{ open: false }">
                    <button id="user-menu-button" aria-label="Account menu" @click="open = !open"
                        @mouseenter="open = true"
                        class="p-2 hover:bg-gray-100 text-gray-700 hover:text-gray-950 focus:outline-none transition-colors rounded-full">
                        <span class="material-symbols-outlined text-green-600">verified_user</span>
                    </button>
                    <div id="user-dropdown" x-show="open" @click.away="open = false" x-transition.opacity
                        class="absolute right-0 top-full mt-1 w-48 rounded-md bg-gray-50 py-1 shadow-lg ring-1 ring-black ring-opacity-5 z-50">
                        <div class="px-4 py-2 border-b border-gray-100">
                            <p class="text-[11px] font-semibold text-gray-900">{{ $this->authUser->name }}</p>
                            <p class="text-[10px] text-gray-500 truncate normal-case tracking-normal">{{ $this->authUser->email }}</p>
                        </div>
                        <a href="{{ route('dashboard') }}"
                            class="block px-4 py-2 text-[11px] uppercase tracking-widest text-gray-700 hover:bg-gray-100">
                            DASHBOARD
                        </a>
                        <a href="{{ route('dashboard.orders') }}" class="block px-4 py-2 text-[11px] uppercase tracking-widest text-gray-700 hover:bg-gray-100">
                            I TUOI ORDINI
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="block">
                            @csrf
                            <button type="submit"
                                class="block w-full text-left px-4 py-2 text-[11px] uppercase tracking-widest text-red-600 hover:bg-gray-100">
                                ESCI
                            </button>
                        </form>
                    </div>
                </div>
                @else
                <button id="account-button" aria-label="Account"
                    class="p-2 hover:bg-gray-100 text-gray-700 hover:text-gray-950 focus:outline-none transition-colors rounded-full hidden lg:block"
                    onclick="openAuthModal()">
                    <span class="material-symbols-outlined">account_circle</span>
                </button>
                @endif

                <!-- Dark Mode Toggle Button -->
                <button id="theme-toggle" aria-label="Toggle dark mode"
                    class="p-2 hover:bg-gray-100 text-gray-700 hover:text-gray-950 focus:outline-none transition-colors rounded-full">
                    <div id="icon-system" class=""><span class="material-symbols-outlined">computer</span></div>
                    <div id="icon-light" class="hidden"><span class="material-symbols-outlined">light_mode</span></div>
                    <div id="icon-dark" class="hidden"><span class="material-symbols-outlined">dark_mode</span></div>
                </button>

                <!-- Mobile menu toggle button -->
                <button type="button" @click="mobileMenuOpen = true" aria-expanded="false" aria-controls="mobile-menu"
                    class="p-2 hover:bg-gray-100 text-gray-700 hover:text-gray-950 focus:outline-none transition-colors lg:hidden rounded-full">
                    <span class="material-symbols-outlined">menu</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Background Backdrop for Mobile Menu -->
    <div class="mega-mobile-backdrop" :class="mobileMenuOpen ? 'pointer-events-auto opacity-100' : ''" @click="mobileMenuOpen = false"></div>

    <!-- Mobile Navigation Drawer -->
    <div x-show="mobileMenuOpen"
        class="fixed inset-0 z-50 bg-gray-950/40 backdrop-blur-sm lg:hidden"
        x-cloak>

        <div class="absolute left-0 top-0 bottom-0 w-[85%] max-w-sm bg-gray-50 border-r border-gray-200 p-6 shadow-2xl flex flex-col justify-between"
            @click.away="mobileMenuOpen = false"
            x-show="mobileMenuOpen"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full">

            <div>
                <div class="flex items-center justify-between pb-4 border-b border-gray-200 mb-6">
                    <span class="text-sm font-bold tracking-[0.25em] text-gray-950">PUBBLICITTA24</span>
                    <button class="text-gray-500 hover:text-gray-950 p-2 font-mono text-lg" @click="mobileMenuOpen = false">✕</button>
                </div>

                <div class="space-y-4 font-mono text-xs uppercase tracking-widest">
                    <!-- Mobile Catalog Accordion -->
                    <div x-data="{ expanded: false }">
                        <button @click="expanded = !expanded" class="flex items-center justify-between w-full py-2 font-semibold text-gray-900">
                            <span>CATALOGO</span>
                            <span class="material-symbols-outlined transition-transform" :class="expanded ? 'rotate-180' : ''">expand_more</span>
                        </button>

                        <div x-show="expanded" x-collapse class="pl-4 mt-2 space-y-3 border-l border-gray-200">
                            @foreach ($this->categories->where('parent_id', null) as $category)
                            <div x-data="{ subExpanded: false }" class="space-y-2">
                                @if ($category->children->isEmpty())
                                <a href="{{ route('category', $category->slug) }}" class="block py-1 text-gray-600 hover:text-accent-500 transition-colors uppercase font-semibold">
                                    {{ mb_strtoupper($category->name) }}
                                </a>
                                @else
                                <button @click="subExpanded = !subExpanded" class="flex items-center justify-between w-full py-1 text-gray-700 uppercase font-semibold">
                                    <span>{{ mb_strtoupper($category->name) }}</span>
                                    <span class="material-symbols-outlined text-[14px] transition-transform duration-300" :class="subExpanded ? 'rotate-180 text-accent-500' : ''">expand_more</span>
                                </button>
                                <div x-show="subExpanded" x-collapse class="pl-3 space-y-2">
                                    @foreach ($category->children as $categoryChild)
                                     <a href="{{ route('category', $categoryChild->slug) }}" class="block py-1 text-[11px] text-gray-500 hover:text-accent-500 transition-colors uppercase font-semibold">
                                        {{ mb_strtoupper($categoryChild->name) }}
                                    </a>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <a href="{{ route('portfolio') }}" class="block py-2 font-semibold text-gray-900 hover:text-accent-500 transition-colors uppercase">
                        PORTFOLIO
                    </a>
                    <a href="{{ route('about') }}" class="block py-2 font-semibold text-gray-900 hover:text-accent-500 transition-colors uppercase">
                        CHI SIAMO
                    </a>
                    <a href="{{ route('services') }}" class="block py-2 font-semibold text-gray-900 hover:text-accent-500 transition-colors uppercase">
                        SERVIZI
                    </a>
                    <a href="{{ route('contact') }}" class="block py-2 font-semibold text-gray-900 hover:text-accent-500 transition-colors uppercase">
                        CONTATTI
                    </a>
                </div>
            </div>

            <div class="pt-6 border-t border-gray-200 flex items-center justify-between">
                @if ($this->authUser)
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-green-600">verified_user</span>
                    <div class="text-[10px]">
                        <div class="font-bold text-gray-900 leading-none uppercase">{{ $this->authUser->name }}</div>
                        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-accent-500 transition-colors uppercase tracking-widest mt-1 block">DASHBOARD</a>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-[10px] text-red-600 hover:underline uppercase tracking-widest">ESCI</button>
                </form>
                @else
                <button class="text-[10px] font-bold text-gray-900 tracking-widest hover:text-accent-500 transition-colors flex items-center gap-1.5" @click="mobileMenuOpen = false; openAuthModal()">
                    <span class="material-symbols-outlined text-sm">account_circle</span>
                    ACCEDI / REGISTRATI
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Search Drawer Modal (Right slide out) - Summer Fridays behavior -->
    <div x-show="searchOpen"
        x-effect="if (searchOpen) { $nextTick(() => { $refs.searchInput.focus(); }); }"
        class="fixed inset-0 z-50 bg-gray-950/40 backdrop-blur-sm"
        @keydown.window.escape="searchOpen = false"
        x-cloak>

        <div class="absolute right-0 top-0 bottom-0 w-full max-w-md bg-gray-50 border-l border-gray-200 p-8 shadow-2xl flex flex-col justify-between"
            @click.away="searchOpen = false"
            x-show="searchOpen"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full">

            <div>
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <span class="font-mono text-xs tracking-widest text-gray-500 uppercase">Cerca nel catalogo</span>
                    <button class="text-gray-500 hover:text-gray-950 p-2 font-mono text-sm uppercase tracking-widest" @click="searchOpen = false">
                        Chiudi ✕
                    </button>
                </div>

                <!-- Search Form -->
                <form action="{{ route('search') }}" method="GET" class="flex w-full items-center">
                    <div class="relative flex w-full items-center border-b border-gray-300 pb-2 focus-within:border-accent-500 transition-colors">
                        <span class="material-symbols-outlined text-gray-400 mr-2">search</span>
                        <label for="nav-search-input" class="sr-only">Cerca prodotti o SKU</label>
                        <input id="nav-search-input" x-ref="searchInput" wire:model.live.debounce.250ms="searchQuery" name="q" type="search" placeholder="Cerca prodotti, SKU..." aria-label="Cerca prodotti o SKU"
                            class="w-full text-xs bg-transparent border-none focus:ring-0 font-mono tracking-widest uppercase text-gray-950 placeholder-gray-400 focus:outline-none">
                    </div>
                </form>

                <!-- Quick Links OR Search Results -->
                <div class="mt-10">
                    @if (empty(trim($searchQuery)))
                        <div>
                            <div class="font-mono text-[10px] tracking-widest text-gray-400 uppercase mb-4">Link Rapidi</div>
                            <div class="flex flex-col gap-4 font-mono text-[11px] uppercase tracking-widest text-gray-950">
                                @foreach ($this->categories->where('parent_id', null) as $category)
                                <a href="{{ route('category', $category->slug) }}" @click="searchOpen = false" class="hover:text-accent-500 transition-colors flex items-center justify-between border-b border-gray-100 pb-2 uppercase">
                                    <span>{{ mb_strtoupper($category->name) }}</span>
                                    <span class="material-symbols-outlined text-xs">arrow_forward</span>
                                </a>
                                @endforeach
                                <a href="{{ route('about') }}" @click="searchOpen = false" class="hover:text-accent-500 transition-colors flex items-center justify-between border-b border-gray-100 pb-2">
                                    <span>Chi Siamo</span>
                                    <span class="material-symbols-outlined text-xs">arrow_forward</span>
                                </a>
                                <a href="{{ route('services') }}" @click="searchOpen = false" class="hover:text-accent-500 transition-colors flex items-center justify-between border-b border-gray-100 pb-2">
                                    <span>Servizi</span>
                                    <span class="material-symbols-outlined text-xs">arrow_forward</span>
                                </a>
                                <a href="{{ route('contact') }}" @click="searchOpen = false" class="hover:text-accent-500 transition-colors flex items-center justify-between border-b border-gray-100 pb-2">
                                    <span>Contatti</span>
                                    <span class="material-symbols-outlined text-xs">arrow_forward</span>
                                </a>
                            </div>
                        </div>
                    @else
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="font-mono text-[10px] tracking-widest text-gray-400 uppercase">Risultati di Ricerca</div>
                                <!-- Loading Spinner -->
                                <div wire:loading wire:target="searchQuery" class="flex items-center gap-1.5 text-[9px] text-gray-400 font-mono uppercase tracking-wider">
                                    <svg class="animate-spin h-3.5 w-3.5 text-accent-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Cerca...
                                </div>
                            </div>

                            <div wire:loading.remove wire:target="searchQuery">
                                @if ($this->searchResults->isEmpty())
                                    <div class="text-xs text-gray-500 font-mono py-8 text-center border border-dashed border-gray-200 rounded-lg">
                                        Nessun prodotto trovato per "<span class="font-semibold text-gray-700">{{ $searchQuery }}</span>".
                                    </div>
                                @else
                                    <div class="flex flex-col gap-3">
                                        @foreach ($this->searchResults as $product)
                                            <a href="{{ route('product', [$product->category->slug, $product->slug]) }}"
                                               @click="searchOpen = false"
                                               class="group flex items-center gap-3 p-2 hover:bg-gray-100 rounded-lg transition-all border border-transparent hover:border-gray-200">
                                                
                                                <!-- Product Thumbnail -->
                                                <div class="h-12 w-12 flex-shrink-0 overflow-hidden rounded bg-gray-100 border border-gray-200 flex items-center justify-center">
                                                    @if($product->getFirstImageUrl('thumbnail'))
                                                        <img src="{{ $product->getFirstImageUrl('thumbnail') }}" alt="{{ $product->name }}" class="h-full w-full object-cover object-center group-hover:scale-105 transition-transform duration-300">
                                                    @else
                                                        <span class="material-symbols-outlined text-gray-400 text-lg">image</span>
                                                    @endif
                                                </div>

                                                <!-- Product Details -->
                                                <div class="flex-1 min-w-0 font-mono normal-case tracking-normal">
                                                    <p class="text-[11px] font-bold text-gray-950 truncate uppercase tracking-wider group-hover:text-accent-500 transition-colors">{{ $product->name }}</p>
                                                    <p class="text-[9px] text-gray-400 truncate mt-0.5">{{ $product->category->name }}</p>
                                                    
                                                    <!-- Pricing -->
                                                    <div class="flex items-center gap-2 mt-1">
                                                        @php
                                                            $priceData = $product->getDisplayPriceData();
                                                        @endphp
                                                        @if ($priceData['on_request'])
                                                            <span class="text-[9px] uppercase tracking-wider text-accent-600 font-bold bg-accent-50 px-1 rounded border border-accent-100">Su Richiesta</span>
                                                        @else
                                                            @if ($priceData['is_discounted'])
                                                                <span class="text-[10px] font-bold text-gray-900 font-mono">€{{ number_format($priceData['price'], 2) }}</span>
                                                                <span class="text-[9px] text-gray-400 line-through font-mono">€{{ number_format($priceData['base_price'], 2) }}</span>
                                                            @else
                                                                <span class="text-[10px] text-gray-600 font-mono">da €{{ number_format($priceData['price'], 2) }}</span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                <!-- Arrow -->
                                                <span class="material-symbols-outlined text-gray-400 group-hover:text-accent-500 transition-colors text-sm pr-1">arrow_forward</span>
                                            </a>
                                        @endforeach
                                    </div>
                                    
                                    <!-- View all search results link -->
                                    <div class="mt-6 pt-4 border-t border-gray-200 text-center">
                                        <a href="{{ route('search', ['q' => $searchQuery]) }}" 
                                           @click="searchOpen = false" 
                                           class="inline-block text-[10px] font-bold text-accent-500 hover:text-accent-600 hover:underline tracking-widest uppercase">
                                            Vedi tutti i risultati
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Bottom Info -->
            <div class="pt-6 border-t border-gray-100 font-mono text-[10px] tracking-wider text-gray-400 uppercase leading-relaxed">
                Hai bisogno di aiuto? <a href="{{ route('contact') }}" @click="searchOpen = false" class="text-accent-500 hover:underline">Contattaci</a> per maggiori informazioni sui nostri prodotti.
            </div>
        </div>
    </div>
</nav>
