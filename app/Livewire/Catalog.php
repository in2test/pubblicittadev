<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Catalog Livewire Component
 *
 * Handles product filtering, sorting, and display of categories and items.
 */
class Catalog extends Component
{
    use WithPagination;

    // Filter properties
    public ?string $categorySlug = null;

    public string $search = '';

    public array $selectedColors = [];

    public array $selectedSizes = [];

    public string $sort = 'name';

    // Query string synchronization
    protected $queryString = [
        'search' => ['except' => ''],
        'sort' => ['except' => 'name'],
    ];

    public function mount(?string $categorySlug = null): void
    {
        $this->categorySlug = $categorySlug;

        // Initial search from query string if not already handled by Livewire
        if ($this->search === '' || $this->search === '0') {
            $this->search = (string) request()->query('search', '');
        }
    }

    /**
     * Reset pagination when filters change
     */
    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'selectedColors', 'selectedSizes', 'categorySlug'])) {
            $this->resetPage();
        }
    }

    /**
     * Category selection logic
     */
    public function selectCategory(string $slug): void
    {
        if ($this->categorySlug === $slug) {
            // Deselect: go to parent category if available
            $category = Category::where('slug', '=', $slug, 'and')->first();
            $this->categorySlug = $category?->parent?->slug ?? null;
        } else {
            $this->categorySlug = $slug;
        }
    }

    public function toggleColor(int $id): void
    {
        if (in_array($id, $this->selectedColors)) {
            $this->selectedColors = array_diff($this->selectedColors, [$id]);
        } else {
            $this->selectedColors[] = $id;
        }
    }

    public function toggleSize(int $id): void
    {
        if (in_array($id, $this->selectedSizes)) {
            $this->selectedSizes = array_diff($this->selectedSizes, [$id]);
        } else {
            $this->selectedSizes[] = $id;
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'selectedColors', 'selectedSizes']);
    }

    /**
     * Computed Properties
     */
    public function getCategoryProperty(): ?Category
    {
        if (! $this->categorySlug) {
            return null;
        }

        return Category::where('slug', '=', $this->categorySlug, 'and')->with('children')->first();
    }

    public function getIsFilteringProperty(): bool
    {
        return $this->search !== '' && $this->search !== '0' || $this->selectedColors !== [] || $this->selectedSizes !== [];
    }

    /**
     * Get colors available for the current filtered product set
     */
    public function getAvailableColorsProperty(): Collection
    {
        $productQuery = $this->getBaseFilteredQuery();

        return Color::whereHas('variations', function ($q) use ($productQuery) {
            $q->whereIn('product_id', $productQuery->select('id'));
        })->orderBy('color_name', 'asc')->get();
    }

    /**
     * Get sizes available for the current filtered product set
     */
    public function getAvailableSizesProperty(): Collection
    {
        $productQuery = $this->getBaseFilteredQuery();

        return Size::whereHas('variations', function ($q) use ($productQuery) {
            $q->whereIn('product_id', $productQuery->select('id'));
        })->orderBy('sort_order', 'asc')->get();
    }

    /**
     * Core query builder for products with basic filters applied
     */
    protected function getBaseFilteredQuery(): Builder
    {
        $category = $this->category;
        $showInactive = Auth::check() && Auth::user()?->isAdmin() === true;

        return Product::query()
            ->when(! $showInactive, fn ($q) => $q->where('is_active', '=', true, 'and'))
            ->when($category, function ($q) use ($category) {
                // Include products in the current category and its direct children
                $ids = $category->children->pluck('id')->push($category->id);
                $q->whereIn('category_id', $ids);
            })
            ->when($this->search !== '' && $this->search !== '0', function ($q) {
                // Use Scout for full-text search if applicable, or fallback to simple LIKE
                // Here we use Scout's database search or keys() depending on config
                $keys = Product::search($this->search)->keys();
                $q->whereIn('id', $keys);
            })
            ->when($this->selectedColors !== [], function ($q) {
                $q->whereHas('variations', fn ($sq) => $sq->whereIn('color_id', $this->selectedColors));
            })
            ->when($this->selectedSizes !== [], function ($q) {
                $q->whereHas('variations', fn ($sq) => $sq->whereIn('size_id', $this->selectedSizes));
            });
    }

    /**
     * Final data fetching for the view
     */
    public function getCatalogData(): array
    {
        $category = $this->category;

        $showInactive = Auth::check() && Auth::user()?->isAdmin() === true;

        // Grouped view: only when viewing a category with children and NO filters are active
        if (! $this->isFiltering && $category && $category->children->isNotEmpty()) {
            $childrenData = $category->children->map(fn ($child) => [
                'category' => $child,
                'products' => $child->products()
                    ->when(! $showInactive, fn ($q) => $q->where('is_active', '=', true, 'and'))
                    ->with(['media', 'category', 'variations.color'])
                    ->take(8)
                    ->get(),
            ]);

            $ownProducts = $category->products()
                ->when(! $showInactive, fn ($q) => $q->where('is_active', '=', true, 'and'))
                ->with(['media', 'category', 'variations.color'])
                ->get();

            return [
                'type' => 'grouped',
                'groups' => $childrenData,
                'standalone' => $ownProducts,
            ];
        }

        // Grid view: searching or flat list
        $products = $this->getBaseFilteredQuery()
            ->with(['media', 'category', 'variations.color'])
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
