@props([
    /**
     * The root categories to display in the tree.
     * @var \Illuminate\Support\Collection<\App\Models\Category>
     */
    'rootCategories',

    /**
     * The slug of the currently selected/active category.
     * @var string|null
     */
    'categorySlug' => null,

    /**
     * The currently active category model instance.
     * @var \App\Models\Category|null
     */
    'category' => null,
])

{{-- 
    Catalog Category Tree Component
    -----------------------------------------------------------------
    This component renders an interactive, multi-level category navigation
    tree. It supports collapsible subcategories when a parent category is active,
    applying high-contrast borders and premium typography.
--}}
<div>
    {{-- Section Header --}}
    <h3 class="text-[10px] font-mono uppercase tracking-[0.3em] text-secondary mb-8 flex items-center gap-3">
        <span class="w-2 h-2 bg-primary"></span>
        Categorie
    </h3>

    {{-- Category List --}}
    <ul class="space-y-4">
        @foreach($rootCategories as $root)
            @php
                // Check if this root category is currently active or one of its descendants is active
                $isActiveRoot = ($categorySlug === $root->slug || ($category && ($category->id === $root->id || in_array($root->id, $category->ancestors()))));
                $isExactRoot = ($categorySlug === $root->slug);
            @endphp
            <li wire:key="category-tree-root-{{ $root->id }}">
                {{-- Root Category Button --}}
                <button
                    wire:click="selectCategory('{{ $root->slug }}')"
                    @class([
                        'w-full text-left transition-all duration-300 flex items-center justify-between group py-2 px-3 rounded uppercase tracking-tight',
                        'text-primary bg-primary/10 border-l-4 border-primary font-black underline decoration-primary decoration-2 underline-offset-4' => $isExactRoot,
                        'text-primary font-bold' => $isActiveRoot && !$isExactRoot,
                        'text-on-surface font-bold hover:text-primary hover:bg-gray-50' => !$isActiveRoot
                    ])
                >
                    <span class="flex items-center gap-2">
                        {{-- Collapsible Indicator Icon --}}
                        <span @class([
                            'material-symbols-outlined text-sm transition-transform',
                            'rotate-90 text-primary font-black' => $isActiveRoot,
                            'text-outline group-hover:text-primary' => !$isActiveRoot
                        ])>chevron_right</span>
                        <span>{{ $root->name }}</span>
                    </span>
                    @if($isExactRoot)
                        <span class="w-1.5 h-1.5 bg-primary rounded-full animate-pulse"></span>
                    @endif
                </button>

                {{-- Children Subcategories --}}
                @if($isActiveRoot && $root->children->isNotEmpty())
                    <ul class="ml-6 mt-3 space-y-2 border-l border-gray-200 pl-4">
                        @foreach($root->children as $child)
                            @php
                                $isActiveChild = ($categorySlug === $child->slug || ($category && ($category->id === $child->id || in_array($child->id, $category->ancestors()))));
                                $isExactChild = ($categorySlug === $child->slug);
                            @endphp
                            <li wire:key="category-tree-child-{{ $child->id }}">
                                <button
                                    wire:click="selectCategory('{{ $child->slug }}')"
                                    @class([
                                        'w-full text-left py-2 px-3 hover:text-primary transition-all duration-200 text-xs font-bold uppercase tracking-tight flex items-center justify-between rounded group',
                                        'text-primary bg-primary/10 border-r-4 border-primary font-black underline decoration-primary decoration-2 underline-offset-4' => $isExactChild,
                                        'text-primary font-bold' => $isActiveChild && !$isExactChild,
                                        'text-secondary hover:bg-gray-50 hover:translate-x-1' => !$isActiveChild
                                    ])
                                >
                                    <span>{{ $child->name }}</span>
                                    @if($isExactChild)
                                        <span class="w-1.5 h-1.5 bg-primary rounded-full animate-pulse"></span>
                                    @elseif($isActiveChild)
                                        <span class="w-1.5 h-1.5 bg-primary rounded-full opacity-60"></span>
                                    @endif
                                </button>

                                {{-- Grandchildren (e.g. 3rd-level) subcategories --}}
                                @if($isActiveChild && $child->children->isNotEmpty())
                                    <ul class="ml-4 mt-2 space-y-1.5 border-l border-gray-200 pl-3">
                                        @foreach($child->children as $grandchild)
                                            @php
                                                $isActiveGrandchild = ($categorySlug === $grandchild->slug);
                                            @endphp
                                            <li wire:key="category-tree-grandchild-{{ $grandchild->id }}">
                                                <button
                                                    wire:click="selectCategory('{{ $grandchild->slug }}')"
                                                    @class([
                                                        'w-full text-left py-1.5 px-2 hover:text-primary transition-all duration-200 text-[11px] font-bold uppercase tracking-tight flex items-center justify-between rounded group',
                                                        'text-primary bg-primary/10 border-r-4 border-primary font-black underline decoration-primary decoration-2 underline-offset-4' => $isActiveGrandchild,
                                                        'text-secondary/80 hover:bg-gray-50 hover:translate-x-1' => !$isActiveGrandchild
                                                    ])
                                                >
                                                    <span>{{ $grandchild->name }}</span>
                                                    @if($isActiveGrandchild)
                                                        <span class="w-1 h-1 bg-primary rounded-full animate-pulse"></span>
                                                    @endif
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</div>
