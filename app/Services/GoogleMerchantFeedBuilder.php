<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\VariationOption;
use Illuminate\Support\Str;
use SimpleXMLElement;

/**
 * Orchestrates building a Google Merchant RSS feed.
 *
 * Responsible for querying products, iterating over them, deciding
 * whether to emit a simple product item or one item per SKU variant,
 * and delegating all XML writing to MerchantFeedItemWriter.
 */
class GoogleMerchantFeedBuilder
{
    public function __construct(
        private readonly ProductPricingService $productPricingService,
        private readonly MerchantFeedItemWriter $itemWriter,
    ) {}

    public function build(): string
    {
        $products = Product::query()->where('is_active', true)
            ->with([
                'media',
                'images',
                'skus.options.variationType',
                'variationTypes',
                'productVariationTypes.options.option',
            ])
            ->get();

        /** @var array<int, Category> $categoryMap */
        $categoryMap = Category::all()->keyBy('id')->all();

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss xmlns:g="http://base.google.com/ns/1.0" version="2.0"></rss>');
        $channel = $xml->addChild('channel');
        $channel->addChild('title', config('app.name', 'Pubblicittà24'));
        $channel->addChild('link', url('/'));
        $channel->addChild('description', 'Catalogo Prodotti '.config('app.name', 'Pubblicittà24'));

        foreach ($products as $product) {
            $category = $categoryMap[$product->category_id] ?? null;
            $categorySlug = $category !== null ? $category->slug : 'uncategorized';
            $categoryName = $category !== null ? $category->name : 'Uncategorized';

            $this->addProductItems($channel, $product, $categorySlug, $categoryName);
        }

        $xmlContent = $xml->asXML();

        return is_string($xmlContent) ? $xmlContent : '';
    }

    private function addProductItems(
        SimpleXMLElement $channel,
        Product $product,
        string $categorySlug,
        string $categoryName,
    ): void {
        if ($product->skus->isEmpty()) {
            $this->addProductItem($channel, $product, $categorySlug, $categoryName);

            return;
        }

        foreach ($product->skus as $sku) {
            $this->addVariantItem($channel, $product, $sku, $categorySlug, $categoryName);
        }
    }

    private function addVariantItem(
        SimpleXMLElement $channel,
        Product $product,
        ProductSku $sku,
        string $categorySlug,
        string $categoryName,
    ): void {
        $options = $this->resolveVariantOptions($sku);
        $colorOption = $options['colorOption'];
        $sizeOption = $options['sizeOption'];
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

        $description = (string) $product->plain_description;
        if ($colorOption) {
            $description .= "\nColore: ".$colorOption->name;
        }
        if ($sizeOption) {
            $description .= "\nTaglia: ".$sizeOption->name;
        }
        if ($sku->isOutlet() && $sku->hasActiveCampaign()) {
            $description .= "\nCampagna valida fino al ".($sku->activeCampaign()?->validityLabel() ?? '');
        }
        $description = $this->appendProductExtendedInfo($description, $product);

        $link = route('product', [$categorySlug, $product->slug]);
        if ($options['queryParams'] !== []) {
            $link .= '?'.http_build_query($options['queryParams']);
        }

        $item = $channel->addChild('item');
        $this->itemWriter->addItemDetails(
            $item,
            $sku->sku ?? ($product->sku.'_'.$sku->id),
            (string) $variantTitle,
            $description,
            htmlspecialchars($link),
            $colorOption?->name,
            $sizeOption?->name,
            $product,
            $colorOption,
        );

        $skuPrice = $this->productPricingService->getSkuPriceForQuantity($product, 1, $sku);
        $item->addChild('g:price', number_format($skuPrice, 2, '.', '').' EUR', 'http://base.google.com/ns/1.0');
        $item->addChild('g:availability', $sku->is_available ? 'in_stock' : 'out_of_stock', 'http://base.google.com/ns/1.0');
        $item->addChild('g:condition', 'new', 'http://base.google.com/ns/1.0');
        $item->addChild('g:brand', $product->brand, 'http://base.google.com/ns/1.0');
        $item->addChild('g:item_group_id', $product->sku, 'http://base.google.com/ns/1.0');
        $this->itemWriter->addCategoryAndDemographics($item, $product, $categorySlug, $categoryName);
    }

    private function addProductItem(
        SimpleXMLElement $channel,
        Product $product,
        string $categorySlug,
        string $categoryName,
    ): void {
        $colorOptions = $product->getColorOptions();
        $description = (string) $product->plain_description;
        if ($colorOptions !== '') {
            $description .= "\nColori: ".$colorOptions;
        }
        $description = $this->appendProductExtendedInfo($description, $product);

        if ($product->sku === null) {
            return;
        }

        $item = $channel->addChild('item');
        $this->itemWriter->addItemDetails(
            $item,
            $product->sku,
            (string) $product->name,
            $description,
            route('product', [$categorySlug, $product->slug]),
            $colorOptions !== '' ? $colorOptions : null,
            null,
            $product,
            null,
        );

        $priceData = $product->getDisplayPriceData();
        $price = number_format((float) $priceData['price'], 2, '.', '');
        $item->addChild('g:price', $price.' EUR', 'http://base.google.com/ns/1.0');
        $item->addChild('g:availability', 'in_stock', 'http://base.google.com/ns/1.0');
        $item->addChild('g:condition', 'new', 'http://base.google.com/ns/1.0');
        $item->addChild('g:brand', $product->brand, 'http://base.google.com/ns/1.0');
        $this->itemWriter->addCategoryAndDemographics($item, $product, $categorySlug, $categoryName);
    }

    /**
     * @return array{
     *     colorOption: VariationOption|null,
     *     sizeOption: VariationOption|null,
     *     queryParams: array<string, mixed>
     * }
     */
    private function resolveVariantOptions(ProductSku $sku): array
    {
        $colorOption = null;
        $sizeOption = null;
        $queryParams = [];

        foreach ($sku->options as $option) {
            $type = $option->variationType;
            if (! $type) {
                continue;
            }

            $typeName = strtolower((string) $type->name);
            if ($type->presentation_type === 'color_swatch'
                || str_contains($typeName, 'color')
                || str_contains($typeName, 'colore')
            ) {
                $colorOption = $option;
            } elseif (str_contains($typeName, 'size') || str_contains($typeName, 'taglia')) {
                $sizeOption = $option;
            }

            if ($type->expose_in_url) {
                $queryParams[Str::slug($type->name)] = $option->value ?: $option->id;
            }
        }

        return [
            'colorOption' => $colorOption,
            'sizeOption' => $sizeOption,
            'queryParams' => $queryParams,
        ];
    }

    private function appendProductExtendedInfo(string $description, Product $product): string
    {
        if (! empty($product->technical_specs)) {
            $description .= "\nComposizione: ".trim(strip_tags((string) $product->technical_specs));
        }

        if (! empty($product->construction_features)) {
            $description .= "\nCaratteristiche: ".trim(strip_tags((string) $product->construction_features));
        }

        if (! empty($product->certifications)) {
            $description .= "\nCertificazioni: ".trim(strip_tags((string) $product->certifications));
        }

        if (! empty($product->customization_notes)) {
            $prefix = $product->type === Product::TYPE_NEWWAVE ? 'Cura del capo: ' : 'Note: ';
            $description .= "\n".$prefix.trim(strip_tags((string) $product->customization_notes));
        }

        return $description;
    }
}
