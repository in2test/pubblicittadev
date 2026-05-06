<div class="flex flex-col lg:flex-row gap-12 px-8 3xl:px-32 mt-12 mb-24 border-t border-gray-200 pt-12">

    {{-- Left Sidebar: Navigation and Filters --}}
    <aside class="w-full lg:w-72 shrink-0">
        <div class="sticky top-24 space-y-12">

            {{-- Categories Tree --}}
            <div>
                <h3 class="text-[10px] font-mono uppercase tracking-[0.3em] text-secondary mb-8 flex items-center gap-3">
                    <span class="w-2 h-2 bg-primary"></span>
                    Categorie
                </h3>
                <ul class="space-y-4">
                    @foreach($rootCategories as $root)
                        <li>
                            <button
                                wire:click="selectCategory('{{ $root->slug }}')"
                                class="w-full text-left transition-all duration-300 flex items-center justify-between group {{ ($categorySlug === $root->slug || ($this->category && $this->category->parent_id === $root->id)) ? 'text-primary font-black' : 'text-on-surface font-bold uppercase tracking-tight' }}">
                                <span class="flex items-center gap-2">
                                    <span @class([
                                        'material-symbols-outlined text-sm transition-transform',
                                        'rotate-90 text-primary' => ($categorySlug === $root->slug || ($this->category && $this->category->parent_id === $root->id)),
                                        'text-outline' => !($categorySlug === $root->slug || ($this->category && $this->category->parent_id === $root->id))
                                    ])>chevron_right</span>
                                    {{ $root->name }}
                                </span>
                            </button>

                            @if($categorySlug === $root->slug || ($this->category && $this->category->parent_id === $root->id))
                                <ul class="ml-6 mt-4 space-y-3 border-l-2 border-gray-100 pl-6">
                                    @foreach($root->children as $child)
                                        <li>
                                            <button
                                                wire:click="selectCategory('{{ $child->slug }}')"
                                                @class([
                                                    'w-full text-left py-1 hover:text-primary transition-all duration-300 text-xs font-bold uppercase tracking-tight',
                                                    'text-primary' => $categorySlug === $child->slug,
                                                    'text-secondary' => $categorySlug !== $child->slug
                                                ])>
                                                {{ $child->name }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Filter: Colors --}}
            @if($this->availableColors->isNotEmpty())
                <div class="pt-10 border-t border-gray-100">
                    <h3 class="text-[10px] font-mono uppercase tracking-[0.3em] text-secondary mb-8 flex items-center gap-3">
                        <span class="w-2 h-2 bg-primary"></span>
                        Colore
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($this->availableColors as $color)
                            @php $isActive = in_array($color->id, $selectedColors); @endphp
                            <button
                                wire:click="toggleColor({{ $color->id }})"
                                @class([
                                    'w-6 h-6 border transition-all duration-200 flex items-center justify-center relative group',
                                    'border-primary ring-2 ring-primary ring-offset-2' => $isActive,
                                    'border-gray-200' => !$isActive
                                ])
                                @style(['background-color: ' . ($color->color_hex ?: '#ccc')])
                                title="{{ $color->color_name }}"
                            >
                                @if($isActive)
                                    <span class="material-symbols-outlined text-[10px] text-white mix-blend-difference">check</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Filter: Sizes --}}
            @if($this->availableSizes->isNotEmpty())
                <div class="pt-10 border-t border-gray-100">
                    <h3 class="text-[10px] font-mono uppercase tracking-[0.3em] text-secondary mb-8 flex items-center gap-3">
                        <span class="w-2 h-2 bg-primary"></span>
                        Taglia
                    </h3>
                    <div class="grid grid-cols-4 gap-1">
                        @foreach($this->availableSizes as $size)
                            @php $isActive = in_array($size->id, $selectedSizes); @endphp
                            <button 
                                wire:click="toggleSize({{ $size->id }})"
                                @class([
                                    'aspect-square border text-[10px] font-mono font-bold uppercase text-center transition-all duration-200 flex items-center justify-center',
                                    'bg-primary text-white border-primary' => $isActive,
                                    'bg-white border-gray-200 text-on-surface hover:border-on-surface' => !$isActive
                                ])
                            >
                                {{ $size->size }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Reset All Filters --}}
            @if($this->isFiltering)
                <div class="pt-10">
                    <button 
                        wire:click="resetFilters"
                        class="w-full py-4 bg-gray-950 text-white text-[10px] font-mono uppercase tracking-[0.3em] hover:bg-primary transition-colors flex items-center justify-center gap-3"
                    >
                        <span class="material-symbols-outlined text-base">close</span>
                        Resetta Filtri
                    </button>
                </div>
            @endif
        </div>
    </aside>

    {{-- Main Content Area --}}
    <main class="flex-1">
        
        {{-- Toolbar: Search and Sort --}}
        <div class="flex flex-col md:flex-row justify-between items-stretch gap-0 mb-16 border border-gray-200">
            {{-- Search Input --}}
            <div class="flex items-center gap-0 w-full md:w-auto flex-1 border-r border-gray-200">
                <form action="{{ route('search') }}" method="GET" class="flex w-full items-center">
                    <span class="px-6 material-symbols-outlined text-gray-400">search</span>
                    <input
                        name="q"
                        type="text"
                        placeholder="Cerca prodotti, SKU..."
                        class="w-full py-5 text-sm bg-transparent border-none focus:ring-0 font-mono tracking-tight"
                    />
                    @if($categorySlug)
                        <input type="hidden" name="category" value="{{ $categorySlug }}">
                    @endif
                </form>
            </div>

            {{-- Sorting --}}
            <div class="flex items-center gap-6 px-8 bg-gray-50">
                <span class="text-[10px] font-mono uppercase tracking-[0.3em] text-secondary">Ordina:</span>
                <select wire:model.live="sort" class="bg-transparent border-none focus:ring-0 text-[10px] uppercase font-bold tracking-tight text-on-surface p-0 cursor-pointer">
                    <option value="name">A-Z</option>
                    <option value="price_asc">Prezzo: +</option>
                    <option value="price_desc">Prezzo: -</option>
                </select>
            </div>
        </div>

        {{-- Products Display --}}
        <div wire:loading.class="opacity-50 pointer-events-none" class="transition-opacity duration-300">
            
            @if($catalogData['type'] === 'grouped')
                {{-- Grouped View (Nested Categories) --}}
                @foreach($catalogData['groups'] as $group)
                    <div class="mb-24">
                        <div class="flex justify-between items-end mb-10 border-b-2 border-gray-900 pb-4">
                            <h2 class="text-4xl font-black uppercase tracking-tighter">
                                {{ $group['category']->name }}
                                <span class="text-secondary text-[10px] font-mono uppercase tracking-[0.3em] ml-6 opacity-40">
                                    {{ $group['category']->products()->count() }} Prodotti
                                </span>
                            </h2>
                            <button
                                wire:click="selectCategory('{{ $group['category']->slug }}')"
                                class="group flex items-center gap-3 text-[10px] font-mono uppercase tracking-[0.3em] text-primary hover:text-gray-950 transition-colors"
                            >
                                Vedi Tutti
                                <span class="material-symbols-outlined text-sm">arrow_right_alt</span>
                            </button>
                        </div>
                        
                        <x-product.grid :products="$group['products']" />
                    </div>
                @endforeach

                {{-- Products directly in this category (if any) --}}
                @if($catalogData['standalone']->isNotEmpty())
                    <div class="mb-24">
                        <div class="flex items-center gap-4 mb-10 border-b-2 border-gray-200 pb-4">
                            <h2 class="text-4xl font-black uppercase tracking-tighter text-secondary">In Evidenza</h2>
                        </div>
                        <x-product.grid :products="$catalogData['standalone']" />
                    </div>
                @endif

            @else
                {{-- Grid View (Search/Flat List) --}}
                <x-product.grid 
                    :products="$catalogData['products']" 
                    reset-action="resetFilters"
                    empty-message="Prova a resettare i filtri per vedere più prodotti."
                />

                {{-- Pagination --}}
                <div class="mt-16">
                    {{ $catalogData['products']->links() }}
                </div>
            @endif
        </div>
    </main>
</div>
