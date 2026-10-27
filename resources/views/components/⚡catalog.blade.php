<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\VariationType;
use App\Models\VariationOption;
use App\Services\ProductSearchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

new class extends Component {
    use WithPagination;

    public ?string $categorySlug = null;
    #[\Livewire\Attributes\Url(except: '')]
    public string $search = '';
    public bool $outletOnly = false;

    public function toggleOutlet(): void
    {
        $this->onlyOutlet = ! $this->onlyOutlet;
    }

    public function toggleOffers(): void
    {
        $this->onlyOffers = ! $this->onlyOffers;
    }
    public array $selectedOptions = [];
    #[\Livewire\Attributes\Url(except: '')]
    public array $selectedBrands = [];
    #[\Livewire\Attributes\Url(except: '')]
    public array $selectedMaterials = [];
    #[\Livewire\Attributes\Url(except: '')]
    public array $selectedGenders = [];
    #[\Livewire\Attributes\Url(except: false)]
    public bool $onlyOutlet = false;
    #[\Livewire\Attributes\Url(except: false)]
    public bool $onlyOffers = false;
    #[\Livewire\Attributes\Url(except: 'name')]
    public string $sort = 'name';
    public bool $isFiltering = false;

    public function mount(?string $categorySlug = null): void
    {
        $this->categorySlug = $categorySlug;
        if (request()->routeIs('outlet')) {
            $this->outletOnly = true;
        }
        if ($this->search === '' || $this->search === '0') {
            $searchQuery = request()->query('search');
            $this->search = is_string($searchQuery) ? $searchQuery : '';
        }
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'selectedOptions', 'selectedBrands', 'selectedMaterials', 'selectedGenders', 'onlyOutlet', 'onlyOffers', 'categorySlug'])) {
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

    public function toggleBrand(int $id): void
    {
        if (in_array($id, $this->selectedBrands)) {
            $this->selectedBrands = array_values(array_diff($this->selectedBrands, [$id]));
        } else {
            $this->selectedBrands[] = $id;
        }
    }

    public function toggleMaterial(int $id): void
    {
        if (in_array($id, $this->selectedMaterials)) {
            $this->selectedMaterials = array_values(array_diff($this->selectedMaterials, [$id]));
        } else {
            $this->selectedMaterials[] = $id;
        }
    }

    public function toggleGender(string $gender): void
    {
        if (in_array($gender, $this->selectedGenders)) {
            $this->selectedGenders = array_values(array_diff($this->selectedGenders, [$gender]));
        } else {
            $this->selectedGenders[] = $gender;
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'selectedOptions', 'selectedBrands', 'selectedMaterials', 'selectedGenders', 'onlyOutlet', 'onlyOffers']);
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
        return $this->search !== '' && $this->search !== '0'
            || $this->selectedOptions !== []
            || $this->selectedBrands !== []
            || $this->selectedMaterials !== []
            || $this->selectedGenders !== []
            || $this->onlyOutlet
            || $this->onlyOffers;
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

    #[Computed]
    public function availableBrands(): Collection
    {
        $productQuery = $this->getBaseFilteredQuery(applyBrandFilter: false);

        return Brand::query()
            ->whereIn('id', $productQuery->whereNotNull('brand_id')->select('brand_id'))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function availableMaterials(): Collection
    {
        $materialType = VariationType::where('name', VariationType::MATERIALE)->first();

        if (! $materialType) {
            return collect();
        }

        $productQuery = $this->getBaseFilteredQuery(applyMaterialFilter: false);

        return VariationOption::query()
            ->where('variation_type_id', $materialType->id)
            ->whereHas('productVariationOptions.productVariationType', function ($q) use ($productQuery) {
                $q->whereIn('product_id', $productQuery->select('id'));
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function availableGenders(): array
    {
        return ['woman', 'man', 'baby', 'unisex'];
    }

    #[Computed]
    public function hasOutletProducts(): bool
    {
        return $this->getBaseFilteredQuery()->hasOutletSkus()->exists();
    }

    #[Computed]
    public function hasOfferProducts(): bool
    {
        return $this->getBaseFilteredQuery()->whereNotNull('offer_price')->where('offer_price', '>', 0)->exists();
    }

    protected function getBaseFilteredQuery(bool $applyBrandFilter = true, bool $applyMaterialFilter = true, bool $applyGenderFilter = true): Builder
    {
        $category = $this->category;
        $showInactive = auth()->check() && auth()->user()?->isAdmin() === true;

        $materialType = VariationType::where('name', VariationType::MATERIALE)->first();

        return Product::query()
            ->when(! $showInactive, fn ($q) => $q->where('is_active', '=', true, 'and'))
            ->when($this->outletOnly, fn ($q) => $q->hasOutletSkus())
            ->when($this->onlyOutlet, fn ($q) => $q->hasOutletSkus())
            ->when($this->onlyOffers, fn ($q) => $q->whereNotNull('offer_price')->where('offer_price', '>', 0))
            ->when($category, function ($q) use ($category) {
                $ids = $category->children->pluck('id')->push($category->id);
                $q->whereIn('category_id', $ids);
            })
            ->when($this->search !== '' && $this->search !== '0', function (\Illuminate\Database\Eloquent\Builder $q) {
                app(ProductSearchService::class)->applySearchTerm($q, $this->search);
            })
            ->when($applyBrandFilter && $this->selectedBrands !== [], fn ($q) => $q->whereIn('brand_id', $this->selectedBrands))
            ->when($applyMaterialFilter && $materialType && $this->selectedMaterials !== [], function ($q) use ($materialType) {
                $q->whereHas('productVariationTypes', function ($sq) use ($materialType) {
                    $sq->where('variation_type_id', $materialType->id)
                       ->whereHas('options', function ($ssq) {
                           $ssq->whereIn('variation_option_id', $this->selectedMaterials);
                       });
                });
            })
            ->when($applyGenderFilter && $this->selectedGenders !== [], function ($q) {
                $selectedKeys = array_map(fn ($gender) => strtolower((string) $gender), $this->selectedGenders);
                $candidateIds = $this->getBaseFilteredQuery(applyGenderFilter: false)
                    ->get()
                    ->filter(function (Product $product) use ($selectedKeys): bool {
                        $genders = is_array($product->genders) ? $product->genders : null;
                        foreach ($selectedKeys as $key) {
                            if ($genders !== null) {
                                if (in_array($key, ['woman', 'women', 'donna', 'female'], true) || str_contains($key, 'woman') || str_contains($key, 'donna')) {
                                    if (in_array('woman', $genders, true)) {
                                        return true;
                                    }
                                } elseif (in_array($key, ['man', 'uomo', 'male'], true) || str_contains($key, 'man') || str_contains($key, 'uomo')) {
                                    if (in_array('man', $genders, true)) {
                                        return true;
                                    }
                                } elseif (in_array($key, ['baby', 'bambino', 'bambina', 'junior', 'kid', 'kids'], true) || str_contains($key, 'baby') || str_contains($key, 'bambin') || str_contains($key, 'junior')) {
                                    if (in_array('baby', $genders, true)) {
                                        return true;
                                    }
                                } elseif ($key === 'unisex') {
                                    if (in_array('unisex', $genders, true)
                                        || (in_array('woman', $genders, true) && in_array('man', $genders, true))) {
                                        return true;
                                    }
                                }
                            } else {
                                $name = $product->name ?? '';
                                if (in_array($key, ['woman', 'women', 'donna', 'female'], true) || str_contains($key, 'woman') || str_contains($key, 'donna')) {
                                    if (str_contains($name, 'Women') || str_contains($name, 'Donna') || str_contains($name, 'Lady') || str_contains($name, 'Ladies')) {
                                        return true;
                                    }
                                } elseif (in_array($key, ['man', 'uomo', 'male'], true) || str_contains($key, 'man') || str_contains($key, 'uomo')) {
                                    if (! str_contains($name, 'Women') && ! str_contains($name, 'Donna') && ! str_contains($name, 'Junior') && ! str_contains($name, 'Girl') && ! str_contains($name, 'Bambin')) {
                                        return true;
                                    }
                                } elseif (in_array($key, ['baby', 'bambino', 'bambina', 'junior', 'kid', 'kids'], true) || str_contains($key, 'baby') || str_contains($key, 'bambin') || str_contains($key, 'junior')) {
                                    if (str_contains($name, 'Junior') || str_contains($name, 'Bambin') || str_contains($name, 'Kid')) {
                                        return true;
                                    }
                                } elseif ($key === 'unisex') {
                                    if (! str_contains($name, 'Women') && ! str_contains($name, 'Donna') && ! str_contains($name, 'Junior') && ! str_contains($name, 'Bambin') && ! str_contains($name, 'Kid')) {
                                        return true;
                                    }
                                }
                            }
                        }

                        return false;
                    })
                    ->pluck('id')
                    ->all();

                if ($candidateIds === []) {
                    $q->whereRaw('1 = 0');
                } else {
                    $q->whereIn('id', $candidateIds);
                }
            })
            ->when($this->selectedOptions !== [], function ($q) {
                $optionsGroupedByType = \App\Models\VariationOption::whereIn('id', $this->selectedOptions)
                    ->get()
                    ->groupBy('variation_type_id');

                foreach ($optionsGroupedByType as $typeId => $options) {
                    $optionIds = $options->pluck('id')->toArray();
                    $q->whereHas('productVariationTypes', function ($sq) use ($typeId, $optionIds) {
                        $sq->where('variation_type_id', $typeId)
                           ->whereHas('options', function ($ssq) use ($optionIds) {
                               $ssq->whereIn('variation_option_id', $optionIds);
                           });
                    });
                }
            });

    }

    #[Computed]
    public function catalogData(): array
    {
        $category = $this->category;
        $showInactive = auth()->check() && auth()->user()?->isAdmin() === true;
        if (! $this->outletOnly && ! $this->getIsFiltering() && $category && $category->children->isNotEmpty()) {
            $category->children->load(['products' => function ($query) use ($showInactive) {
                $query->when(! $showInactive, fn ($q) => $q->where('is_active', '=', true, 'and'))
                    ->select([
                        'id', 'name', 'slug', 'sku', 'description', 'price', 
                        'offer_price', 'pricing_model', 'is_featured', 'is_active', 
                        'category_id', 'cached_starting_price', 'cached_starting_unit_price',
                        'brand_id'
                    ])
                    ->with([
                        'media' => fn($q) => $q->where('collection_name', 'images')->orderBy('order_column')->limit(1), 
                        'images' => fn($q) => $q->orderBy('order_by')->limit(1),
                        'category:id,name,slug', 
                        'brand_relation:id,name,slug',
                        'productVariationTypes' => fn($q) => $q->where('has_images', true)->select('id', 'product_id', 'variation_type_id', 'has_images'),
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
                    'category_id', 'cached_starting_price', 'cached_starting_unit_price',
                    'brand_id'
                ])
                ->with([
                    'media' => fn($q) => $q->where('collection_name', 'images')->orderBy('order_column')->limit(1), 
                    'images' => fn($q) => $q->orderBy('order_by')->limit(1),
                    'category:id,name,slug', 
                    'brand_relation:id,name,slug',
                    'productVariationTypes' => fn($q) => $q->where('has_images', true)->select('id', 'product_id', 'variation_type_id', 'has_images'),
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

        if (! $this->outletOnly && ! $this->getIsFiltering() && ! $category) {
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
                        'category_id', 'cached_starting_price', 'cached_starting_unit_price',
                        'brand_id'
                    ])
                    ->with([
                        'media' => fn($q) => $q->where('collection_name', 'images')->orderBy('order_column')->limit(1), 
                        'images' => fn($q) => $q->orderBy('order_by')->limit(1),
                        'category:id,name,slug', 
                        'brand_relation:id,name,slug',
                        'productVariationTypes' => fn($q) => $q->where('has_images', true)->select('id', 'product_id', 'variation_type_id', 'has_images'),
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
                'category_id', 'cached_starting_price', 'cached_starting_unit_price',
                'brand_id'
            ])
            ->with([
                'media' => fn($q) => $q->where('collection_name', 'images')->orderBy('order_column')->limit(1), 
                'images' => fn($q) => $q->orderBy('order_by')->limit(1),
                'category:id,name,slug', 
                'brand_relation:id,name,slug',
                'productVariationTypes' => fn($q) => $q->where('has_images', true)->select('id', 'product_id', 'variation_type_id', 'has_images'),
                'productVariationTypes.options:id,product_variation_type_id,variation_option_id,sort_order',
                'productVariationTypes.options.option:id,name,value,color_hex'
            ])
            ->orderBy(
                ($this->outletOnly && $this->sort === 'name')
                    ? 'id'
                    : ($this->sort === 'price_asc' ? 'price' : ($this->sort === 'price_desc' ? 'price' : 'name')),
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

<div class="flex flex-col lg:flex-row gap-12 px-6 lg:px-12 3xl:px-24 mt-4 mb-24 border-t border-gray-200 pt-4 lg:pt-8">

    {{-- Left Sidebar: Navigation and Filters --}}
    <x-catalog.sidebar
        :root-categories="$this->rootCategories"
        :category-slug="$categorySlug"
        :category="$this->category"
        :available-variation-types="$this->availableVariationTypes"
        :available-brands="$this->availableBrands"
        :available-materials="$this->availableMaterials"
        :available-genders="$this->availableGenders"
        :selected-options="$selectedOptions"
        :selected-brands="$selectedBrands"
        :selected-materials="$selectedMaterials"
        :selected-genders="$selectedGenders"
        :only-outlet="$onlyOutlet"
        :only-offers="$onlyOffers"
        :has-outlet-products="$this->hasOutletProducts"
        :has-offer-products="$this->hasOfferProducts"
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

