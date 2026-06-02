<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use SimpleXMLElement;

class GoogleMerchantFeedController extends Controller
{
    public function index(): Response
    {
        $products = Product::where('is_active', true)
            ->with(['media'])
            ->get();

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss xmlns:g="http://base.google.com/ns/1.0" version="2.0"></rss>');
        $channel = $xml->addChild('channel');
        $channel->addChild('title', config('app.name', 'Pubblicittà24'));
        $channel->addChild('link', url('/'));
        $channel->addChild('description', 'Catalogo Prodotti '.config('app.name', 'Pubblicittà24'));

        foreach ($products as $product) {
            $item = $channel->addChild('item');

            // Requisiti Base
            $item->addChild('g:id', $product->sku, 'http://base.google.com/ns/1.0');
            $item->addChild('g:title', htmlspecialchars((string) $product->name), 'http://base.google.com/ns/1.0');
            $item->addChild('g:description', htmlspecialchars((string) $product->plain_description), 'http://base.google.com/ns/1.0');
            $item->addChild('g:link', route('product', [$product->category->slug ?? 'uncategorized', $product->slug]), 'http://base.google.com/ns/1.0');

            $imageUrl = $product->hasMedia('images')
                ? $product->getFirstMediaUrl('images', 'large')
                : url('/placeholder.png');
            $item->addChild('g:image_link', $imageUrl, 'http://base.google.com/ns/1.0');

            // Prezzo e Disponibilità
            $priceData = $product->getDisplayPriceData();
            $price = number_format((float) $priceData['price'], 2, '.', '');
            $item->addChild('g:price', $price.' EUR', 'http://base.google.com/ns/1.0');

            // Availability
            $item->addChild('g:availability', 'in_stock', 'http://base.google.com/ns/1.0');

            // Condizione
            $item->addChild('g:condition', 'new', 'http://base.google.com/ns/1.0');

            // Brand
            $item->addChild('g:brand', htmlspecialchars((string) $product->brand), 'http://base.google.com/ns/1.0');

            // Categoria (opzionale ma consigliato per abbigliamento)
            $item->addChild('g:product_type', htmlspecialchars((string) $product->category->name), 'http://base.google.com/ns/1.0');

            // Update time
            $item->addChild('g:updated_at', Carbon::parse($product->updated_at)->toRfc3339String(), 'http://base.google.com/ns/1.0');
        }

        $xmlContent = $xml->asXML();

        return response(is_string($xmlContent) ? $xmlContent : '', 200)
            ->header('Content-Type', 'text/xml');
    }
}
