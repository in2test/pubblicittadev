<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * CollectionController manages the display of product bundles and collections.
 */
class CollectionController extends Controller
{
    /**
     * Display a listing of active collections / bundles.
     */
    public function index(): View
    {
        $collections = Collection::query()
            ->active()
            ->with([
                'media',
                'products' => fn ($q) => $q->where('is_active', true)
                    ->with(['media', 'images', 'category:id,name,slug'])
                    ->take(4),
            ])
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('collections.index', [
            'collections' => $collections,
        ]);
    }

    /**
     * Display the specified collection / bundle and its products.
     */
    public function show(Collection $collection): View
    {
        abort_unless($collection->is_active || Auth::check(), 404);

        $collection->load([
            'media',
            'products' => fn ($q) => $q->where('is_active', true)
                ->select([
                    'products.id',
                    'products.name',
                    'products.slug',
                    'products.sku',
                    'products.description',
                    'products.price',
                    'products.offer_price',
                    'products.pricing_model',
                    'products.is_featured',
                    'products.is_active',
                    'products.category_id',
                    'products.cached_starting_price',
                    'products.cached_starting_unit_price',
                    'products.created_at',
                ])
                ->with([
                    'category:id,name,slug',
                    'productVariationTypes' => fn ($query) => $query->where('has_images', true)->select('id', 'product_id', 'variation_type_id', 'has_images'),
                    'productVariationTypes.options:id,product_variation_type_id,variation_option_id,sort_order',
                    'productVariationTypes.options.option:id,name,value,color_hex',
                    'media' => fn ($query) => $query->orderBy('order_column')->limit(1),
                    'images' => fn ($query) => $query->orderBy('order_by')->limit(1),
                ]),
        ]);

        return view('collections.show', [
            'collection' => $collection,
        ]);
    }
}
