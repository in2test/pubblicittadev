@props([
    /**
     * The current search query string.
     * @var string
     */
    'search' => '',

    /**
     * The slug of the currently selected category.
     * @var string|null
     */
    'categorySlug' => null,
])

{{-- 
    Catalog Toolbar Component
    -----------------------------------------------------------------
    A top-level utility bar featuring a full-width search input and
    a structured sort selector. Designed with a robust, industrial,
    high-contrast border layout that integrates seamlessly on all viewports.
--}}
<div class="flex flex-col md:flex-row justify-between items-stretch gap-0 mb-16 border border-gray-200 bg-white">
    {{-- Search Input Section --}}
    <div class="flex items-center gap-0 w-full md:w-auto flex-1 border-b md:border-b-0 md:border-r border-gray-200">
        <form action="{{ route('search') }}" method="GET" class="flex w-full items-center">
            {{-- Search Icon --}}
            <span class="px-6 material-symbols-outlined text-gray-400 select-none">search</span>
            
            {{-- Search Field --}}
            <label for="catalog-search-input" class="sr-only">Cerca prodotti o SKU</label>
            <input
                id="catalog-search-input"
                name="q"
                type="text"
                value="{{ $search }}"
                placeholder="Cerca prodotti, SKU..."
                aria-label="Cerca prodotti o SKU"
                class="w-full py-5 pr-6 text-sm bg-transparent border-none focus:ring-0 font-mono tracking-tight text-on-surface placeholder-gray-400"
            />
            
            {{-- Category Context Hidden Field --}}
            @if($categorySlug)
                <input type="hidden" name="category" value="{{ $categorySlug }}">
            @endif
        </form>
    </div>

    {{-- Sorting Section --}}
    <div class="flex items-center gap-6 px-8 py-4 md:py-0 bg-gray-50">
        <label for="catalog-sort-select" class="sr-only">Ordina prodotti per</label>
        <span class="text-[10px] font-mono uppercase tracking-[0.3em] text-secondary select-none">Ordina:</span>
        <select 
            id="catalog-sort-select"
            wire:model.live="sort" 
            aria-label="Ordina prodotti per"
            class="bg-transparent border-none focus:ring-0 text-[10px] uppercase font-bold tracking-tight text-on-surface p-0 cursor-pointer focus:outline-none"
        >
            <option value="name">A-Z</option>
            <option value="price_asc">Prezzo: +</option>
            <option value="price_desc">Prezzo: -</option>
        </select>
    </div>
</div>
