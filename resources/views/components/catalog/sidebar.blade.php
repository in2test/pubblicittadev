@props([
    /**
     * Root categories for the tree.
     */
    'rootCategories',

    /**
     * Selected category slug.
     */
    'categorySlug' => null,

    /**
     * Currently selected Category model.
     */
    'category' => null,

    /**
     * Available variation types matching current catalog.
     */
    'availableVariationTypes',

    /**
     * Array of selected variation option IDs.
     */
    'selectedOptions' => [],

    /**
     * Whether any active filters are applied.
     */
    'isFiltering' => false,
])

{{-- 
    Catalog Sidebar Component
    -----------------------------------------------------------------
    A modern, premium sidebar housing the Category Tree navigation and
    interactive attribute filters (e.g. colors, sizes). This component
    keeps the main layout clean and focuses on a fluid, industrial sidebar experience.
--}}
<aside class="w-full lg:w-72 shrink-0">
    <div class="sticky top-24 space-y-12">
        {{-- 1. Category Tree Navigation --}}
        <x-catalog.category-tree
            :root-categories="$rootCategories"
            :category-slug="$categorySlug"
            :category="$category"
        />

        {{-- 2. Attribute Filters (Colors, Sizes, etc.) --}}
        <x-catalog.filters
            :available-variation-types="$availableVariationTypes"
            :selected-options="$selectedOptions"
        />

        {{-- 3. Reset Filters Action --}}
        @if($isFiltering)
            <div class="pt-10">
                <button 
                    wire:click="resetFilters"
                    class="w-full py-4 bg-gray-950 text-gray-50 text-[10px] font-mono uppercase tracking-[0.3em] hover:bg-primary transition-colors flex items-center justify-center gap-3 border-2 border-gray-950 hover:border-primary active:scale-95 duration-200"
                    type="button"
                >
                    <span class="material-symbols-outlined text-base">close</span>
                    Resetta Filtri
                </button>
            </div>
        @endif
    </div>
</aside>
