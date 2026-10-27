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
     * Brands available for the current catalog query.
     */
    'availableBrands',

    /**
     * Materials available for the current catalog query.
     */
    'availableMaterials',

    /**
     * Genders available for the current catalog query.
     */
    'availableGenders',
    /**
     * Array of selected variation option IDs.
     */
    'selectedOptions' => [],

    /**
     * Array of selected brand IDs.
     */
    'selectedBrands' => [],

    /**
     * Array of selected material IDs.
     */
    'selectedMaterials' => [],

    /**
     * Array of selected gender filters.
     */
    'selectedGenders' => [],

    /**
     * Whether to restrict results to outlet products.
     */
    'onlyOutlet' => false,

    /**
     * Whether to restrict results to discounted/offer products.
     */
    'onlyOffers' => false,
    'hasOutletProducts' => false,
    'hasOfferProducts' => false,

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
    <div class="sticky top-24 space-y-12 overflow-y-auto max-h-[calc(100vh-140px)] scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-none pr-4">
        {{-- 1. Category Tree Navigation --}}
        <x-catalog.category-tree
            :root-categories="$rootCategories"
            :category-slug="$categorySlug"
            :category="$category"
        />

        {{-- 2. Brand Filter --}}
        <x-catalog.brand-filter
            :available-brands="$availableBrands"
            :selected-brands="$selectedBrands"
        />

        {{-- 2b. Material Filter --}}
        <x-catalog.material-filter
            :available-materials="$availableMaterials"
            :selected-materials="$selectedMaterials"
        />

        {{-- 2c. Gender Filter --}}
        <x-catalog.gender-filter
            :available-genders="$availableGenders"
            :selected-genders="$selectedGenders"
        />

        {{-- 2d. Quick toggles --}}
        @if ($hasOutletProducts || $hasOfferProducts)
            <div class="pt-6 border-t border-gray-200">
                <div class="flex flex-wrap gap-2">
                    @if ($hasOutletProducts)
                        <button
                            wire:click="toggleOutlet"
                            type="button"
                            @class([
                                'border text-[10px] font-mono font-bold uppercase text-center transition-all duration-200 flex items-center justify-center px-3 py-2',
                                'bg-primary text-white border-primary' => $onlyOutlet,
                                'bg-gray-50 border-gray-200 text-on-surface hover:border-on-surface' => ! $onlyOutlet,
                            ])
                        >
                            Outlet
                        </button>
                    @endif
                    @if ($hasOfferProducts)
                        <button
                            wire:click="toggleOffers"
                            type="button"
                            @class([
                                'border text-[10px] font-mono font-bold uppercase text-center transition-all duration-200 flex items-center justify-center px-3 py-2',
                                'bg-primary text-white border-primary' => $onlyOffers,
                                'bg-gray-50 border-gray-200 text-on-surface hover:border-on-surface' => ! $onlyOffers,
                            ])
                        >
                            Offerte
                        </button>
                    @endif
                </div>
            </div>
        @endif

        {{-- 3. Attribute Filters (Colors, Sizes, etc.) --}}
        <x-catalog.filters
            :available-variation-types="$availableVariationTypes"
            :selected-options="$selectedOptions"
        />

        {{-- 4. Reset Filters Action --}}
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
