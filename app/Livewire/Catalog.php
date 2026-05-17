<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use App\Models\VariationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Catalog Livewire Component
 */
class Catalog extends Component
{
    use WithPagination;

    public ?string $categorySlug = null;

    public string $search = '';

    public array $selectedOptions = [];

    public string $sort = 'name';

    public ?Category $category = null;

    public bool $isFiltering = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'sort' => ['except' => 'name'],
    ];

    public function mount(?string $categorySlug = null): void
    {
        $this->categorySlug = $categorySlug;
        if ($this->search === '' || $this->search === '0') {
            $this->search = (string) request()->query('search', '');
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

    public function getCategory(): ?Category
    {
        if (! $this->categorySlug) {
            return null;
        }

        return Category::where('slug', '=', $this->categorySlug, 'and')->with('children')->first();
    }

    public function getIsFiltering(): bool
    {
        return $this->search !== '' && $this->search !== '0' || $this->selectedOptions !== [];
    }

    public function getAvailableVariationTypesProperty(): Collection
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
        $category = $this->getCategory();
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
                // If they picked options, products must have these options in their SKUs
                $q->whereHas('skus.options', fn ($sq) => $sq->whereIn('variation_options.id', $this->selectedOptions));
            });
    }

    public function getCatalogData(): array
    {
        $category = $this->getCategory();
        $showInactive = auth()->check() && auth()->user()?->isAdmin() === true;

        if (! $this->getIsFiltering() && $category && $category->children->isNotEmpty()) {
            $childrenData = $category->children->map(fn ($child) => [
                'category' => $child,
                'products' => $child->products()
                    ->when(! $showInactive, fn ($q) => $q->where('is_active', '=', true, 'and'))
                    ->with(['media', 'category', 'variationTypes.options'])
                    ->take(8)
                    ->get(),
            ]);

            $ownProducts = $category->products()
                ->when(! $showInactive, fn ($q) => $q->where('is_active', '=', true, 'and'))
                ->with(['media', 'category', 'variationTypes.options'])
                ->get();

            return [
                'type' => 'grouped',
                'groups' => $childrenData,
                'standalone' => $ownProducts,
            ];
        }

        $products = $this->getBaseFilteredQuery()
            ->with(['media', 'category', 'variationTypes.options'])
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

    public function render()
    {
        return view('livewire.catalog', [
            'catalogData' => $this->getCatalogData(),
            'rootCategories' => Category::whereNull('parent_id', 'and', false)->with('children')->get(),
        ]);
    }
}
