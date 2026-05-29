<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\Category;
use App\Models\Product;
use App\Models\VariationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

new class extends Component {
    use WithPagination;

    public ?string $categorySlug = null;
    public string $search = '';
    public array $selectedOptions = [];
    public string $sort = 'name';
    public bool $isFiltering = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'sort' => ['except' => 'name'],
    ];

    public function mount(?string $categorySlug = null): void
    {
        $this->categorySlug = $categorySlug;
        if ($this->search === '' || $this->search === '0') {
            $searchQuery = request()->query('search');
            $this->search = is_string($searchQuery) ? $searchQuery : '';
        }
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'selectedOptions', 'categorySlug'])) {
            $this->resetPage();
        }
    }

    public function selectCategory(string $slug): mixed
    {
        if ($this->categorySlug === $slug) {
            /** @var Category|null $category */
            $category = Category::where('slug', '=', $slug, 'and')->first();
            /** @var Category|null $parent */
            $parent = $category?->parent;
            $this->categorySlug = $parent->slug ?? null;
        } else {
            $this->categorySlug = $slug;
        }

        return $this->categorySlug
            ? redirect()->route('category', ['category' => $this->categorySlug])
            : redirect()->route('catalog');
    }

    public function toggleOption(int $id): void
    {
        if (in_array($id, $this->selectedOptions)) {
            $this->selectedOptions = array_diff($this->selectedOptions, [$id]);
        } else {
            $this->selectedOptions[] = $id;
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'selectedOptions']);
    }

    #[Computed]
    public function category(): ?Category
    {
        if (! $this->categorySlug) {
            return null;
        }

        return Category::where('slug', '=', $this->categorySlug, 'and')
            ->with(['children' => fn ($q) => $q->withCount('products'), 'parent.parent'])
            ->first();
    }

    public function getIsFiltering(): bool
    {
        return $this->search !== '' && $this->search !== '0' || $this->selectedOptions !== [];
    }

    #[Computed]
    public function availableVariationTypes(): Collection
    {
        $productQuery = $this->getBaseFilteredQuery();

        return VariationType::whereHas('productVariationTypes', function ($q) use ($productQuery) {
            $q->whereIn('product_id', $productQuery->select('id'));
        })->with(['options' => function ($q) use ($productQuery) {
            $q->whereHas('productVariationOptions.productVariationType', function ($sq) use ($productQuery) {
                $sq->whereIn('product_id', $productQuery->select('id'));
            })->orderBy('sort_order');
        }])->get();
    }

    protected function getBaseFilteredQuery(): Builder
    {
        $category = $this->category;
        $showInactive = auth()->check() && auth()->user()?->isAdmin() === true;

        return Product::query()
            ->when(! $showInactive, fn ($q) => $q->where('is_active', '=', true, 'and'))
            ->when($category, function ($q) use ($category) {
                $ids = $category->children->pluck('id')->push($category->id);
                $q->whereIn('category_id', $ids);
            })
            ->when($this->search !== '' && $this->search !== '0', function ($q) {
                $searchTerm = $this->search;
                $words = preg_split('/\s+/', trim($searchTerm), -1, PREG_SPLIT_NO_EMPTY);

                foreach ($words as $word) {
                    $q->where(function ($sq) use ($word) {
                        $sq->where('name', 'like', "%{$word}%")
                            ->orWhere('sku', 'like', "%{$word}%")
                            ->orWhere('description', 'like', "%{$word}%")
                            ->orWhereHas('category', fn ($cq) => $cq->where('name', 'like', "%{$word}%"));
                    });
                }
            })
            ->when($this->selectedOptions !== [], function ($q) {
                $q->whereHas('skus.options', fn ($sq) => $sq->whereIn('variation_options.id', $this->selectedOptions));
            });
    }

    #[Computed]
    public function catalogData(): array
    {
        $category = $this->category;
        $showInactive = auth()->check() && auth()->user()?->isAdmin() === true;
        if (! $this->getIsFiltering() && $category && $category->children->isNotEmpty()) {
            $category->children->load(['products' => function ($query) use ($showInactive) {
                $query->when(! $showInactive, fn ($q) => $q->where('is_active', '=', true, 'and'))
                    ->select([
                        'id', 'name', 'slug', 'sku', 'description', 'price', 
                        'offer_price', 'pricing_model', 'is_featured', 'is_active', 
                        'category_id', 'cached_starting_price', 'cached_starting_unit_price'
                    ])
                    ->with([
                        'media' => fn($q) => $q->where('collection_name', 'images')->orderBy('order_column')->limit(1), 
                        'category:id,name,slug', 
                        'productVariationTypes:id,product_id,variation_type_id,has_images',
                        'productVariationTypes.options:id,product_variation_type_id,variation_option_id,sort_order',
                        'productVariationTypes.options.option:id,name,value,color_hex'
                    ])
                    ->take(8);
            }]);
            $category->children->loadCount(['products' => function ($query) use ($showInactive) {
                $query->when(! $showInactive, fn ($q) => $q->where('is_active', '=', true, 'and'));
            }]);
            $childrenData = $category->children->map(fn ($child) => [
                'category' => $child,
                'products' => $child->products,
                'total_products_count' => $child->products_count,
            ]);
            $ownProducts = $category->products()
                ->when(! $showInactive, fn ($q) => $q->where('is_active', '=', true, 'and'))
                ->select([
                    'id', 'name', 'slug', 'sku', 'description', 'price', 
                    'offer_price', 'pricing_model', 'is_featured', 'is_active', 
                    'category_id', 'cached_starting_price', 'cached_starting_unit_price'
                ])
                ->with([
                    'media' => fn($q) => $q->where('collection_name', 'images')->orderBy('order_column')->limit(1), 
                    'category:id,name,slug', 
                    'productVariationTypes:id,product_id,variation_type_id,has_images',
                    'productVariationTypes.options:id,product_variation_type_id,variation_option_id,sort_order',
                    'productVariationTypes.options.option:id,name,value,color_hex'
                ])
                ->get();
            return [
                'type' => 'grouped',
                'groups' => $childrenData,
                'standalone' => $ownProducts,
            ];
        }

        if (! $this->getIsFiltering() && ! $category) {
            $rootCategories = Category::whereNull('parent_id')->with('children')->get();
            $groups = $rootCategories->map(function ($root) use ($showInactive): array {
                $categoryIds = $root->children->pluck('id')->push($root->id);
                
                $totalCount = \App\Models\Product::whereIn('category_id', $categoryIds)
                    ->when(! $showInactive, fn ($q) => $q->where('is_active', '=', true, 'and'))
                    ->count();

                $products = \App\Models\Product::whereIn('category_id', $categoryIds)
                    ->when(! $showInactive, fn ($q) => $q->where('is_active', '=', true, 'and'))
                    ->select([
                        'id', 'name', 'slug', 'sku', 'description', 'price', 
                        'offer_price', 'pricing_model', 'is_featured', 'is_active', 
                        'category_id', 'cached_starting_price', 'cached_starting_unit_price'
                    ])
                    ->with([
                        'media' => fn($q) => $q->where('collection_name', 'images')->orderBy('order_column')->limit(1), 
                        'category:id,name,slug', 
                        'productVariationTypes:id,product_id,variation_type_id,has_images',
                        'productVariationTypes.options:id,product_variation_type_id,variation_option_id,sort_order',
                        'productVariationTypes.options.option:id,name,value,color_hex'
                    ])
                    ->take(8)
                    ->get();
                    
                return [
                    'category' => $root,
                    'products' => $products,
                    'total_products_count' => $totalCount,
                ];
            });
            return [
                'type' => 'grouped',
                'groups' => $groups,
            ];
        }

        $products = $this->getBaseFilteredQuery()
            ->select([
                'id', 'name', 'slug', 'sku', 'description', 'price', 
                'offer_price', 'pricing_model', 'is_featured', 'is_active', 
                'category_id', 'cached_starting_price', 'cached_starting_unit_price'
            ])
            ->with([
                'media' => fn($q) => $q->where('collection_name', 'images')->orderBy('order_column')->limit(1), 
                'category:id,name,slug', 
                'productVariationTypes:id,product_id,variation_type_id,has_images',
                'productVariationTypes.options:id,product_variation_type_id,variation_option_id,sort_order',
                'productVariationTypes.options.option:id,name,value,color_hex'
            ])
            ->orderBy(
                $this->sort === 'price_asc' ? 'price' : ($this->sort === 'price_desc' ? 'price' : 'name'),
                $this->sort === 'price_desc' ? 'desc' : 'asc'
            )
            ->paginate(12);

        return [
            'type' => 'grid',
            'products' => $products,
        ];
    }

    #[Computed]
    public function rootCategories(): Collection
    {
        return Category::whereNull('parent_id', 'and', false)->with('children.children')->get();
    }
};
?>

<div class="flex flex-col lg:flex-row gap-12 px-8 3xl:px-32 mt-12 mb-24 border-t border-gray-200 pt-12">

    {{-- Left Sidebar: Navigation and Filters --}}
    <x-catalog.sidebar
        :root-categories="$this->rootCategories"
        :category-slug="$categorySlug"
        :category="$this->category"
        :available-variation-types="$this->availableVariationTypes"
        :selected-options="$selectedOptions"
        :is-filtering="$this->getIsFiltering()"
    />

    {{-- Main Content Area --}}
    <main class="flex-1">
        
        {{-- Toolbar: Search, Filtering, and Sorting options --}}
        <x-catalog.toolbar
            :search="$search"
            :category-slug="$categorySlug"
        />

        {{-- Products Grid/Grouped Display & Pagination --}}
        <x-catalog.results
            :catalog-data="$this->catalogData"
            :category-slug="$categorySlug"
            :search="$search"
        />

    </main>
</div>

