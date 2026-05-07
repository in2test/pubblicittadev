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

    public ?Category $category = null;

    public bool $isFiltering = false;

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
    /**
     * Handles the selection of a category from the sidebar.
     *
     * This method updates the active category filter and redirects the user
     * to the corresponding category route to maintain SEO-friendly URLs.
     *
     * @param  string  $slug  The slug of the category to select.
     * @return mixed Returns a redirect response or void.
     */
    public function selectCategory(string $slug): mixed
    {
        if ($this->categorySlug === $slug) {
            // If the category is already selected, attempt to "deselect" it
            // by moving up to the parent category, or clear the filter.
            $category = Category::where('slug', '=', $slug, 'and')->first();
            $this->categorySlug = $category?->parent?->slug ?? null;
        } else {
            // Otherwise, set the selected category
            $this->categorySlug = $slug;
        }

        /**
         * Navigation logic:
         * Redirect to the specific category route if a slug is active,
         * otherwise return to the base catalog route.
         *
         * We use 'mixed' return type because Livewire's redirect helper
         * returns a Livewire-specific Redirector object rather than
         * a standard Symfony RedirectResponse.
         */
        return $this->categorySlug
            ? redirect()->route('category', ['category' => $this->categorySlug])
            : redirect()->route('catalog');
    }

    /**
     * Toggles the selection of a color filter.
     *
     * If the color is already selected, it is removed from the active filters.
     * Otherwise, it is added to the selection.
     *
     * @param  int  $id  The ID of the color to toggle.
     */
    public function toggleColor(int $id): void
    {
        if (in_array($id, $this->selectedColors)) {
            $this->selectedColors = array_diff($this->selectedColors, [$id]);
        } else {
            $this->selectedColors[] = $id;
        }
    }

    /**
     * Toggles the selection of a size filter.
     *
     * If the size is already selected, it is removed from the active filters.
     * Otherwise, it is added to the selection.
     *
     * @param  int  $id  The ID of the size to toggle.
     */
    public function toggleSize(int $id): void
    {
        if (in_array($id, $this->selectedSizes)) {
            $this->selectedSizes = array_diff($this->selectedSizes, [$id]);
        } else {
            $this->selectedSizes[] = $id;
        }
    }

    /**
     * Resets all active product filters (search, colors, and sizes).
     *
     * This clears the current filtering state, returning the catalog to its
     * default view.
     */
    public function resetFilters(): void
    {
        $this->reset(['search', 'selectedColors', 'selectedSizes']);
    }

    /**
     * Resolves the current category object based on the active slug.
     *
     * @return Category|null The current category or null if no category is selected.
     */
    public function getCategory(): ?Category
    {
        if (! $this->categorySlug) {
            return null;
        }

        return Category::where('slug', '=', $this->categorySlug, 'and')->with('children')->first();
    }

    /**
     * Determines if any active filters (search, colors, or sizes) are currently applied.
     *
     * @return bool True if the catalog is currently filtered.
     */
    public function getIsFiltering(): bool
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
     * Builds the core Eloquent query for products with active filters applied.
     *
     * This method handles:
     * 1. Visibility filtering (active products for guests, all for admins).
     * 2. Category filtering (including children of the selected category).
     * 3. Keyword search (split into words for inclusive matching across product and category).
     * 4. Variation filtering (color and size matching).
     *
     * @return Builder The configured product query builder.
     */
    protected function getBaseFilteredQuery(): Builder
    {
        $category = $this->getCategory();
        $showInactive = Auth::check() && Auth::user()?->isAdmin() === true;

        return Product::query()
            ->when(! $showInactive, fn ($q) => $q->where('is_active', '=', true, 'and'))
            ->when($category, function ($q) use ($category) {
                // Include products in the current category and its direct children
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
            ->when($this->selectedColors !== [], function ($q) {
                $q->whereHas('variations', fn ($sq) => $sq->whereIn('color_id', $this->selectedColors));
            })
            ->when($this->selectedSizes !== [], function ($q) {
                $q->whereHas('variations', fn ($sq) => $sq->whereIn('size_id', $this->selectedSizes));
            });
    }

    /**
     * Prepares and fetches data for the catalog view.
     *
     * This method decides whether to return a "Grouped" view (showing children categories
     * when no filters are active) or a "Grid" view (a flat list of products when searching
     * or filtering).
     *
     * @return array {
     *               'type': 'grouped'|'grid',
     *               'groups': array|null,
     *               'standalone': Collection|null,
     *               'products': Paginator|null
     *               }
     */
    public function getCatalogData(): array
    {
        $category = $this->getCategory();

        $showInactive = Auth::check() && Auth::user()?->isAdmin() === true;

        // Grouped view: only when viewing a category with children and NO filters are active
        if (! $this->getIsFiltering() && $category && $category->children->isNotEmpty()) {
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
