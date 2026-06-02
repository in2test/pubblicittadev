<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PortfolioItem;
use App\Models\Product;
use Illuminate\View\View;

/**
 * HomePageController manages the landing page and the initial product showcase.
 *
 * It handles the logic for selecting a mix of featured and latest products
 * to ensure the homepage always has a full and relevant product grid.
 */
class HomePageController extends Controller
{
    /**
     * Display the homepage.
     *
     * This method retrieves a set of products for the homepage. It prioritizes
     * 'featured' products first. If the number of featured products is less
     * than the required 9, it fills the remaining slots with the latest non-featured products.
     *
     * @return View The rendered welcome view.
     */
    public function index(): View
    {
        $products = Product::active()
            ->select([
                'id',
                'name',
                'slug',
                'sku',
                'description',
                'price',
                'offer_price',
                'pricing_model',
                'is_featured',
                'is_active',
                'category_id',
                'cached_starting_price',
                'cached_starting_unit_price',
                'created_at',
            ])
            ->with([
                'category:id,name,slug',
                'productVariationTypes' => fn ($q) => $q->where('has_images', true)->select('id', 'product_id', 'variation_type_id', 'has_images'),
                'productVariationTypes.options:id,product_variation_type_id,variation_option_id,sort_order',
                'productVariationTypes.options.option:id,name,value,color_hex',
                'media' => fn ($query) => $query->orderBy('order_column')->limit(1),
                'images' => fn ($query) => $query->orderBy('order_by')->limit(1),
            ])
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->take(9)
            ->get();

        $portfolioItems = PortfolioItem::has('media')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->take(3)
            ->get();

        $portfolioCount = $portfolioItems->count();
        $productsNeeded = 6 - $portfolioCount;

        $carouselProducts = Product::active()
            ->where(function ($q) {
                $q->has('media')->orHas('images');
            })
            ->with([
                'media' => fn ($query) => $query->orderBy('order_column')->limit(1),
                'images' => fn ($query) => $query->orderBy('order_by')->limit(1),
            ])
            ->orderByDesc('is_featured')
            ->inRandomOrder()
            ->take($productsNeeded)
            ->get();

        $heroSlides = [];

        foreach ($portfolioItems as $item) {
            $heroSlides[] = [
                'img' => $item->getFirstMediaUrl('portfolio_images'),
                'label' => mb_strtoupper((string) $item->title),
                'sub' => 'PROGETTO PORTFOLIO',
            ];
        }

        foreach ($carouselProducts as $item) {
            $img = $item->getFirstMediaUrl('product_images');
            if (! $img && $item->images->first()) {
                $firstImg = $item->images->first();
                $img = $firstImg->image_url ?: ($firstImg->image_path ? asset('storage/'.$firstImg->image_path) : '');
            }

            $heroSlides[] = [
                'img' => $img,
                'label' => mb_strtoupper((string) $item->name),
                'sub' => 'REF: '.($item->sku ?? 'PROD-'.$item->id),
            ];
        }

        $defaultSlides = [
            ['img' => 'https://images.nwgmedia.com/standard/715867/028230_BasicPolo_ss26_v9%20copy.jpg', 'label' => 'STAMPA ALTA DEFINIZIONE', 'sub' => 'REF: PB-2024'],
            ['img' => 'https://images.nwgmedia.com/standard/725895/028242_114_ClassicPolowomens_SS26_2.jpg', 'label' => 'MATERIALI PREMIUM', 'sub' => 'REF: MAT-100'],
            ['img' => 'https://images.nwgmedia.com/standard/740333/028250_99_SoftshellJacket_SS26_4.jpg', 'label' => 'ABBIGLIAMENTO LAVORO', 'sub' => 'REF: WORK-99'],
            ['img' => 'https://images.nwgmedia.com/standard/715867/028230_BasicPolo_ss26_v9%20copy.jpg', 'label' => 'QUALITÀ GARANTITA', 'sub' => 'REF: PB-2025'],
            ['img' => 'https://images.nwgmedia.com/standard/725895/028242_114_ClassicPolowomens_SS26_2.jpg', 'label' => 'PERSONALIZZAZIONE', 'sub' => 'REF: MAT-101'],
            ['img' => 'https://images.nwgmedia.com/standard/740333/028250_99_SoftshellJacket_SS26_4.jpg', 'label' => 'SUPPORTO UMANO', 'sub' => 'REF: WORK-100'],
        ];

        $c = count($heroSlides);
        if ($c < 6) {
            for ($i = $c; $i < 6; $i++) {
                $heroSlides[] = $defaultSlides[$i];
            }
        }

        return view('welcome', [
            'products' => $products,
            'heroSlides' => $heroSlides,
        ]);
    }
}
