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
            // 1. Fetch all active products with their media, images, and variants pre-loaded to optimize database queries.
            $products = Product::where('is_active', true)
                ->with([
                    'media',
                    'images',
                    'skus.options.variationType',
                    'variationTypes',
                    'productVariationTypes.options.option',
                ])
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
                // 4. Resolve category slug and name safely (avoids PHPStan nullsafe type-checking warnings on non-nullable checks).
                $category = $categoryMap[$product->category_id] ?? null;
                $categorySlug = $category !== null ? $category->slug : 'uncategorized';
                $categoryName = $category !== null ? $category->name : 'Uncategorized';

                $skus = $product->skus;

                if ($skus->isNotEmpty()) {
                    // Generate a separate item for each variant (ProductSku)
                    foreach ($skus as $sku) {
                        $item = $channel->addChild('item');

                        // Resolve variant options (color and size)
                        $colorOption = null;
                        $sizeOption = null;

                        foreach ($sku->options as $option) {
                            $type = $option->variationType;
                            if ($type) {
                                $typeName = strtolower((string) $type->name);
                                if ($type->presentation_type === 'color_swatch'
                                    || str_contains($typeName, 'color')
                                    || str_contains($typeName, 'colore')
                                ) {
                                    $colorOption = $option;
                                } elseif (str_contains($typeName, 'size')
                                    || str_contains($typeName, 'taglia')
                                ) {
                                    $sizeOption = $option;
                                }
                            }
                        }

                        // Unique ID for the variant
                        $variantId = $sku->sku ?? ($product->sku.'_'.$sku->id);
                        $item->addChild('g:id', $variantId, 'http://base.google.com/ns/1.0');

                        // Title with variant details (e.g. "Product Name (Color, Size)")
                        $titleDetails = [];
                        if ($colorOption) {
                            $titleDetails[] = $colorOption->name;
                        }
                        if ($sizeOption) {
                            $titleDetails[] = $sizeOption->name;
                        }
                        $variantTitle = $product->name;
                        if ($titleDetails !== []) {
                            $variantTitle .= ' ('.implode(', ', $titleDetails).')';
                        }
                        $item->addChild('g:title', htmlspecialchars((string) $variantTitle), 'http://base.google.com/ns/1.0');
                        $item->addChild('g:language', 'it', 'http://base.google.com/ns/1.0');
                        $item->addChild('g:target_country', 'IT', 'http://base.google.com/ns/1.0');

                        // Enriched description specific to this variant
                        $enrichedDesc = (string) $product->plain_description;
                        if ($colorOption) {
                            $enrichedDesc .= "\nColore: ".$colorOption->name;
                        }
                        if ($sizeOption) {
                            $enrichedDesc .= "\nTaglia: ".$sizeOption->name;
                        }
                        $item->addChild('g:description', htmlspecialchars($enrichedDesc), 'http://base.google.com/ns/1.0');

                        // Specific color attribute
                        if ($colorOption) {
                            $item->addChild('g:color', htmlspecialchars((string) $colorOption->name), 'http://base.google.com/ns/1.0');
                        }

                        // Specific size attribute
                        if ($sizeOption) {
                            $item->addChild('g:size', htmlspecialchars((string) $sizeOption->name), 'http://base.google.com/ns/1.0');
                        }

                        // Link and specific image
                        $item->addChild('g:link', route('product', [$categorySlug, $product->slug]), 'http://base.google.com/ns/1.0');

                        $imageUrl = null;
                        if ($colorOption) {
                            $skuImage = $product->getImagesForOption($colorOption->id)->first();
                            if ($skuImage) {
                                $imageUrl = $skuImage->large ?? $skuImage->url;
                            }
                        }
                        if (! $imageUrl) {
                            $imageUrl = $product->getFirstImageUrl('large');
                        }
                        $item->addChild('g:image_link', $imageUrl, 'http://base.google.com/ns/1.0');

                        // Specific price for this SKU
                        $skuPrice = $product->getPriceForQuantity(1, $sku);
                        $price = number_format((float) $skuPrice, 2, '.', '');
                        $item->addChild('g:price', $price.' EUR', 'http://base.google.com/ns/1.0');

                        // Availability & condition
                        $availability = $sku->is_available ? 'in_stock' : 'out_of_stock';
                        $item->addChild('g:availability', $availability, 'http://base.google.com/ns/1.0');
                        $item->addChild('g:condition', 'new', 'http://base.google.com/ns/1.0');

                        // Brand, item group ID, and product type
                        $item->addChild('g:brand', htmlspecialchars((string) $product->brand), 'http://base.google.com/ns/1.0');
                        $item->addChild('g:item_group_id', $product->sku, 'http://base.google.com/ns/1.0');
                        $item->addChild('g:product_type', htmlspecialchars($categoryName), 'http://base.google.com/ns/1.0');

                        // Gender & age group for apparel
                        $nameLower = strtolower((string) $product->name);
                        $gender = 'unisex';
                        if (str_contains($nameLower, 'donna') || str_contains($nameLower, 'women') || str_contains($nameLower, 'lady') || str_contains($nameLower, 'ladies')) {
                            $gender = 'female';
                        } elseif (str_contains($nameLower, 'uomo') || str_contains($nameLower, ' men') || str_contains($nameLower, 'man')) {
                            $gender = 'male';
                        }
                        $item->addChild('g:gender', $gender, 'http://base.google.com/ns/1.0');
                        $item->addChild('g:age_group', 'adult', 'http://base.google.com/ns/1.0');
                    }
                } else {
                    // Generate a single item for the parent product if it has no variants
                    $item = $channel->addChild('item');

                    $item->addChild('g:id', $product->sku, 'http://base.google.com/ns/1.0');
                    $item->addChild('g:title', htmlspecialchars((string) $product->name), 'http://base.google.com/ns/1.0');
                    $item->addChild('g:language', 'it', 'http://base.google.com/ns/1.0');
                    $item->addChild('g:target_country', 'IT', 'http://base.google.com/ns/1.0');

                    $colorOptions = $product->getColorOptions();

                    $enrichedDesc = (string) $product->plain_description;
                    if ($colorOptions !== '') {
                        $enrichedDesc .= "\nColori: ".$colorOptions;
                    }
                    $item->addChild('g:description', htmlspecialchars($enrichedDesc), 'http://base.google.com/ns/1.0');

                    if ($colorOptions !== '') {
                        $item->addChild('g:color', htmlspecialchars($colorOptions), 'http://base.google.com/ns/1.0');
                    }

                    $item->addChild('g:link', route('product', [$categorySlug, $product->slug]), 'http://base.google.com/ns/1.0');
                    $imageUrl = $product->getFirstImageUrl('large');
                    $item->addChild('g:image_link', $imageUrl, 'http://base.google.com/ns/1.0');

                    $priceData = $product->getDisplayPriceData();
                    $price = number_format((float) $priceData['price'], 2, '.', '');
                    $item->addChild('g:price', $price.' EUR', 'http://base.google.com/ns/1.0');

                    $item->addChild('g:availability', 'in_stock', 'http://base.google.com/ns/1.0');
                    $item->addChild('g:condition', 'new', 'http://base.google.com/ns/1.0');

                    $item->addChild('g:brand', htmlspecialchars((string) $product->brand), 'http://base.google.com/ns/1.0');
                    $item->addChild('g:product_type', htmlspecialchars($categoryName), 'http://base.google.com/ns/1.0');

                    // Gender & age group for apparel
                    $nameLower = strtolower((string) $product->name);
                    $gender = 'unisex';
                    if (str_contains($nameLower, 'donna') || str_contains($nameLower, 'women') || str_contains($nameLower, 'lady') || str_contains($nameLower, 'ladies')) {
                        $gender = 'female';
                    } elseif (str_contains($nameLower, 'uomo') || str_contains($nameLower, ' men') || str_contains($nameLower, 'man')) {
                        $gender = 'male';
                    }
                    $item->addChild('g:gender', $gender, 'http://base.google.com/ns/1.0');
                    $item->addChild('g:age_group', 'adult', 'http://base.google.com/ns/1.0');
                }
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
