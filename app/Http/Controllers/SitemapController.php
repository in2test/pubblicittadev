<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        /** @var Collection<int, Product> $products */
        $products = Product::where('is_active', true)->with('category')->get();

        /** @var Collection<int, Category> $categories */
        $categories = Category::whereHas('products', fn ($q) => $q->where('is_active', true))
            ->orWhereHas('children.products', fn ($q) => $q->where('is_active', true))
            ->get();

        // 1. Initialize XML string layout
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://sitemaps.org">';

        // 2. Append Static Pages
        $staticPages = [
            'home' => 'daily',
            'about' => 'monthly',
            'services' => 'monthly',
            'portfolio' => 'weekly',
            'contact' => 'monthly',
        ];

        foreach ($staticPages as $route => $freq) {
            $priority = $route === 'home' ? '1.0' : '0.8';
            $xml .= '<url><loc>'.route($route).'</loc><lastmod>'.now()->toAtomString()."</lastmod><changefreq>{$freq}</changefreq><priority>{$priority}</priority></url>";
        }

        // 3. Append Catalog Root & Categories
        $xml .= '<url><loc>'.route('catalog').'</loc><lastmod>'.now()->toAtomString().'</lastmod><changefreq>daily</changefreq><priority>0.9</priority></url>';

        foreach ($categories as $category) {
            $lastmod = $category->updated_at ? $category->updated_at->toAtomString() : now()->toAtomString();
            $xml .= '<url><loc>'.route('category', $category).'</loc><lastmod>'.$lastmod.'</lastmod><changefreq>weekly</changefreq><priority>0.9</priority></url>';
        }

        // 4. Append Products
        foreach ($products as $product) {
            // PHPStan already knows $product->category is evaluated correctly via PHPDoc
            /** @var string $productUrl */
            $productUrl = $product->url;
            $lastmod = $product->updated_at ? $product->updated_at->toAtomString() : now()->toAtomString();

            $xml .= '<url><loc>'.e($productUrl).'</loc><lastmod>'.$lastmod.'</lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>';
        }

        $xml .= '</urlset>';

        // 5. Directly type-cast or type-hint the response array method cleanly
        /** @var Response $response */
        $response = response($xml, 200);

        return $response->header('Content-Type', 'text/xml');
    }
}
