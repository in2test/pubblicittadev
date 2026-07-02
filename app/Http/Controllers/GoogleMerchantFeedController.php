<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;
use SimpleXMLElement;
use Throwable;

class GoogleMerchantFeedController extends Controller
{
    public function index(): Response
    {
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        try {
            // 1. Fetch all active products with their media and images pre-loaded to optimize database queries.
            $products = Product::where('is_active', true)
                ->with(['media', 'images'])
                ->get();

            // 2. Fetch all categories and map them by ID for O(1) category lookup within the loop.
            /** @var array<int, Category> $categoryMap */
            $categoryMap = Category::all()->keyBy('id')->all();

            // 3. Initialize RSS 2.0 XML with the Google Merchant namespace ('g').
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss xmlns:g="http://base.google.com/ns/1.0" version="2.0"></rss>');
            $channel = $xml->addChild('channel');
            $channel->addChild('title', config('app.name', 'Pubblicittà24'));
            $channel->addChild('link', url('/'));
            $channel->addChild('description', 'Catalogo Prodotti '.config('app.name', 'Pubblicittà24'));

            foreach ($products as $product) {
                $item = $channel->addChild('item');

                // 4. Resolve category slug and name safely (avoids PHPStan nullsafe type-checking warnings on non-nullable checks).
                $category = $categoryMap[$product->category_id] ?? null;
                $categorySlug = $category !== null ? $category->slug : 'uncategorized';
                $categoryName = $category !== null ? $category->name : 'Uncategorized';

                // 5. Add required Google Merchant Base Fields.
                $item->addChild('g:id', $product->sku, 'http://base.google.com/ns/1.0');
                $item->addChild('g:title', htmlspecialchars((string) $product->name), 'http://base.google.com/ns/1.0');
                $item->addChild('g:language', 'it', 'http://base.google.com/ns/1.0');
                $item->addChild('g:target_country', 'IT', 'http://base.google.com/ns/1.0');

                // 6. Fetch color options directly from the product variations and append to the description.
                $colorOptions = $product->getColorOptions();

                // Enriched description contains the plain description and lists available color options.
                $enrichedDesc = (string) $product->plain_description;
                if ($colorOptions !== '') {
                    $enrichedDesc .= "\nColori: ".$colorOptions;
                }
                $item->addChild('g:description', htmlspecialchars($enrichedDesc), 'http://base.google.com/ns/1.0');

                // Optional single-string color list (comma-separated).
                if ($colorOptions !== '') {
                    $item->addChild('g:color', htmlspecialchars($colorOptions), 'http://base.google.com/ns/1.0');
                }

                // 7. Product link and image link.
                $item->addChild('g:link', route('product', [$categorySlug, $product->slug]), 'http://base.google.com/ns/1.0');
                $imageUrl = $product->getFirstImageUrl('large');
                $item->addChild('g:image_link', $imageUrl, 'http://base.google.com/ns/1.0');

                // 8. Resolve final/effective pricing (incorporating discounts/tiers) and format as decimal string.
                $priceData = $product->getDisplayPriceData();
                $price = number_format((float) $priceData['price'], 2, '.', '');
                $item->addChild('g:price', $price.' EUR', 'http://base.google.com/ns/1.0');

                // 9. Standard mercantile fields (in_stock availability, new condition).
                $item->addChild('g:availability', 'in_stock', 'http://base.google.com/ns/1.0');
                $item->addChild('g:condition', 'new', 'http://base.google.com/ns/1.0');

                // 10. Brand and Google product type.
                $item->addChild('g:brand', htmlspecialchars((string) $product->brand), 'http://base.google.com/ns/1.0');
                $item->addChild('g:product_type', htmlspecialchars($categoryName), 'http://base.google.com/ns/1.0');
            }

            $xmlContent = $xml->asXML();

            return response(is_string($xmlContent) ? $xmlContent : '', 200)
                ->header('Content-Type', 'text/xml');
        } catch (Throwable $e) {
            // Log fallback error handler in plain text for easier feed debugging.
            return response($e->getMessage()."\n".$e->getTraceAsString(), 500)
                ->header('Content-Type', 'text/plain');
        }
    }
}
