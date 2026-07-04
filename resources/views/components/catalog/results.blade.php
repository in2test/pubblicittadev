@props([
    /**
     * Computed catalog data containing type (grouped/grid), groups, standalone products, or paginate results.
     * @var array{type: string, groups?: \Illuminate\Support\Collection, standalone?: \Illuminate\Support\Collection, products?: \Illuminate\Contracts\Pagination\LengthAwarePaginator}
     */
    'catalogData',

    /**
     * Currently active category slug context.
     * @var string|null
     */
    'categorySlug' => null,

    /**
     * Current search query string.
     * @var string
     */
    'search' => '',
])

{{-- 
    Catalog Results Component
    -----------------------------------------------------------------
    This component handles rendering the products catalog display in two modes:
    1. Grouped View: Shows subcategories nested inside the parent category with their respective grid.
    2. Grid View: Flat paginated grid used when filtering, searching, or displaying flat categories.
    Designed with a strong focus on readability, robust layout transitions, and high-performance bindings.
--}}
<div wire:loading.class="opacity-50 pointer-events-none" class="transition-opacity duration-300">
    
    @if($catalogData['type'] === 'grouped')
        
        {{-- ==========================================
             1. Grouped View (Nested Subcategories)
             ========================================== --}}
        @foreach($catalogData['groups'] as $group)
            @if($group['products']->isNotEmpty())
                <div class="mb-24" wire:key="catalog-group-{{ $group['category']->id }}">
                    {{-- Subcategory Title & 'View All' CTA --}}
                    <div class="flex justify-between items-end mb-10 border-b-2 border-gray-900 pb-4">
                        <h2 class="text-4xl font-black uppercase tracking-tighter text-on-surface">
                            {{ $group['category']->name }}
                            <span class="text-secondary text-[10px] font-mono uppercase tracking-[0.3em] ml-6 opacity-40">
                                {{ $group['total_products_count'] ?? ($group['category']->products_count ?? $group['category']->products()->count()) }} Prodotti
                            </span>
                        </h2>
                        
                        <button
                            wire:click="selectCategory('{{ $group['category']->slug }}')"
                            class="group flex items-center gap-3 text-[10px] font-mono uppercase tracking-[0.3em] text-primary hover:text-gray-950 transition-colors"
                            type="button"
                        >
                            Vedi Tutti
                            <span class="material-symbols-outlined text-sm transition-transform group-hover:translate-x-1">arrow_right_alt</span>
                        </button>
                    </div>
                    
                    {{-- Subcategory Products Grid --}}
                    <x-product.grid :products="$group['products']" />
                </div>
            @endif
        @endforeach

        {{-- ==========================================
             2. Standalone Products (Directly in Parent)
             ========================================== --}}
        @if(isset($catalogData['standalone']) && $catalogData['standalone']->isNotEmpty())
            <div class="mb-24">
                <div class="flex items-center gap-4 mb-10 border-b-2 border-gray-200 pb-4">
                    <h2 class="text-4xl font-black uppercase tracking-tighter text-secondary">
                        In Evidenza
                    </h2>
                </div>
                
                {{-- Standalone Products Grid --}}
                <x-product.grid :products="$catalogData['standalone']" />
            </div>
        @endif

    @else
        
        {{-- ==========================================
             3. Flat Grid View (Search, Filters, or Leaf Categories)
             ========================================== --}}
        {{-- Title and Product Count Feedback --}}
        <div class="flex justify-between items-end mb-10 border-b-2 border-gray-900 pb-4">
            <h2 class="text-4xl font-black uppercase tracking-tighter text-on-surface">
                @if($categorySlug && $category = \App\Models\Category::where('slug', $categorySlug)->first())
                    {{ $category->name }}
                @elseif($search !== '' && $search !== '0')
                    Risultati Ricerca
                @else
                    Tutti i Prodotti
                @endif
                <span class="text-secondary text-[10px] font-mono uppercase tracking-[0.3em] ml-6 opacity-40">
                    {{ $catalogData['products']->total() }} {{ $catalogData['products']->total() === 1 ? 'Prodotto' : 'Prodotti' }}
                </span>
            </h2>
        </div>

        <x-product.grid 
            :products="$catalogData['products']" 
            reset-action="resetFilters"
            empty-message="Prova a resettare i filtri o inserire un'altra parola chiave per trovare prodotti."
        />

        {{-- Pagination Controls --}}
        @if($catalogData['products'] instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $catalogData['products']->hasPages())
            <div class="mt-16 border-t border-gray-200 pt-8">
                {{ $catalogData['products']->links() }}
            </div>
        @endif

    @endif
</div>

@php
    $itemListElements = [];
    $position = 1;

    if ($catalogData['type'] === 'grouped') {
        foreach ($catalogData['groups'] as $group) {
            foreach ($group['products'] as $p) {
                $itemListElements[] = [
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'url' => $p->url,
                    'item' => [
                        '@type' => 'Product',
                        'name' => $p->name,
                        'url' => $p->url,
                        'image' => $p->getFirstImageUrl('medium'),
                        'sku' => $p->sku,
                        'offers' => $p->isOnRequest() ? null : [
                            '@type' => 'Offer',
                            'priceCurrency' => 'EUR',
                            'price' => number_format((float) ($p->getStartingPrice() ?: 0), 2, '.', '')
                        ]
                    ]
                ];
            }
        }
        if (isset($catalogData['standalone'])) {
            foreach ($catalogData['standalone'] as $p) {
                $itemListElements[] = [
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'url' => $p->url,
                    'item' => [
                        '@type' => 'Product',
                        'name' => $p->name,
                        'url' => $p->url,
                        'image' => $p->getFirstImageUrl('medium'),
                        'sku' => $p->sku,
                        'offers' => $p->isOnRequest() ? null : [
                            '@type' => 'Offer',
                            'priceCurrency' => 'EUR',
                            'price' => number_format((float) ($p->getStartingPrice() ?: 0), 2, '.', '')
                        ]
                    ]
                ];
            }
        }
    } else {
        foreach ($catalogData['products'] as $p) {
            $itemListElements[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'url' => $p->url,
                'item' => [
                    '@type' => 'Product',
                    'name' => $p->name,
                    'url' => $p->url,
                    'image' => $p->getFirstImageUrl('medium'),
                    'sku' => $p->sku,
                    'offers' => $p->isOnRequest() ? null : [
                        '@type' => 'Offer',
                        'priceCurrency' => 'EUR',
                        'price' => number_format((float) ($p->getStartingPrice() ?: 0), 2, '.', '')
                    ]
                ]
            ];
        }
    }

    $itemListSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'itemListElement' => $itemListElements
    ];
@endphp

@if(!empty($itemListElements))
<script type="application/ld+json">
{!! json_encode($itemListSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endif
