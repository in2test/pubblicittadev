<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\VariationOption;
use Illuminate\Support\Str;
use SimpleXMLElement;

class GoogleMerchantFeedBuilder
{
    private const string MERCHANT_NAMESPACE = 'http://base.google.com/ns/1.0';

    public function __construct(private readonly ProductPricingService $productPricingService) {}

    public function build(): string
    {
        $products = Product::where('is_active', true)
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

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss xmlns:g="'.self::MERCHANT_NAMESPACE.'" version="2.0"></rss>');
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

        $link = route('product', [$categorySlug, $product->slug]);
        if ($options['queryParams'] !== []) {
            $link .= '?'.http_build_query($options['queryParams']);
        }

        $item = $channel->addChild('item');
        $this->addItemDetails(
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
        $item->addChild('g:price', number_format($skuPrice, 2, '.', '').' EUR', self::MERCHANT_NAMESPACE);
        $item->addChild('g:availability', $sku->is_available ? 'in_stock' : 'out_of_stock', self::MERCHANT_NAMESPACE);
        $item->addChild('g:condition', 'new', self::MERCHANT_NAMESPACE);
        $item->addChild('g:brand', 'CLIQUE', self::MERCHANT_NAMESPACE);
        $item->addChild('g:item_group_id', $product->sku, self::MERCHANT_NAMESPACE);
        $this->addCategoryAndDemographics($item, $product, $categorySlug, $categoryName);
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

        $item = $channel->addChild('item');
        $this->addItemDetails(
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
        $item->addChild('g:price', $price.' EUR', self::MERCHANT_NAMESPACE);
        $item->addChild('g:availability', 'in_stock', self::MERCHANT_NAMESPACE);
        $item->addChild('g:condition', 'new', self::MERCHANT_NAMESPACE);
        $item->addChild('g:brand', 'CLIQUE', self::MERCHANT_NAMESPACE);
        $this->addCategoryAndDemographics($item, $product, $categorySlug, $categoryName);
    }

    private function addItemDetails(
        SimpleXMLElement $item,
        string $id,
        string $title,
        string $description,
        string $link,
        ?string $color,
        ?string $size,
        Product $product,
        ?VariationOption $colorOption,
    ): void {
        $item->addChild('g:id', $id, self::MERCHANT_NAMESPACE);
        $item->addChild('g:title', htmlspecialchars($title), self::MERCHANT_NAMESPACE);
        $item->addChild('g:language', 'it', self::MERCHANT_NAMESPACE);
        $item->addChild('g:target_country', 'IT', self::MERCHANT_NAMESPACE);
        $item->addChild('g:description', htmlspecialchars($description), self::MERCHANT_NAMESPACE);

        if ($color !== null) {
            $item->addChild('g:color', htmlspecialchars($color), self::MERCHANT_NAMESPACE);
        }

        if ($size !== null) {
            $item->addChild('g:size', htmlspecialchars($size), self::MERCHANT_NAMESPACE);
        }

        $item->addChild('g:link', $link, self::MERCHANT_NAMESPACE);
        $this->addImageLinks($item, $product, $colorOption);
    }

    private function addCategoryAndDemographics(
        SimpleXMLElement $item,
        Product $product,
        string $categorySlug,
        string $categoryName,
    ): void {
        $item->addChild('g:product_type', htmlspecialchars($categoryName), self::MERCHANT_NAMESPACE);
        $item->addChild(
            'g:google_product_category',
            htmlspecialchars($this->getGoogleProductCategory($categorySlug)),
            self::MERCHANT_NAMESPACE,
        );

        $nameLower = strtolower((string) $product->name);
        $gender = 'unisex';
        if (str_contains($nameLower, 'donna') || str_contains($nameLower, 'women') || str_contains($nameLower, 'lady') || str_contains($nameLower, 'ladies')) {
            $gender = 'female';
        } elseif (str_contains($nameLower, 'uomo') || str_contains($nameLower, ' men') || str_contains($nameLower, 'man')) {
            $gender = 'male';
        }
        $item->addChild('g:gender', $gender, self::MERCHANT_NAMESPACE);

        $ageGroup = 'adult';
        if (str_contains($nameLower, 'junior') || str_contains($nameLower, 'bambin') || str_contains($nameLower, 'kid')) {
            $ageGroup = 'kids';
        }
        $item->addChild('g:age_group', $ageGroup, self::MERCHANT_NAMESPACE);
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

    /**
     * Map category slug to official Google Product Category taxonomy.
     */
    private function getGoogleProductCategory(string $slug): string
    {
        return match ($slug) {
            't-shirts-and-tops' => 'Apparel & Accessories > Clothing > Shirts & Tops',
            'giacche' => 'Apparel & Accessories > Clothing > Outerwear > Coats & Jackets',
            'abbigliamento-da-lavoro' => 'Apparel & Accessories > Clothing > Uniforms',
            default => 'Apparel & Accessories > Clothing',
        };
    }

    private function addImageLinks(SimpleXMLElement $item, Product $product, ?VariationOption $colorOption): void
    {
        $variantImages = $colorOption instanceof VariationOption ? $product->getImagesForOption($colorOption->id) : collect();
        $genericImages = $product->getImagesForOption(null);
        $imageCandidates = $colorOption instanceof VariationOption
            ? $variantImages->concat($genericImages)
            : $genericImages;

        $imageUrl = null;
        if ($imageCandidates->isNotEmpty()) {
            $imageAttributes = (array) $imageCandidates->first();
            $imageUrl = $imageAttributes['large'] ?? $imageAttributes['url'] ?? null;
        }
        $imageUrl ??= $product->getFirstImageUrl('large');

        $item->addChild('g:image_link', htmlspecialchars($imageUrl, ENT_XML1 | ENT_QUOTES, 'UTF-8'), self::MERCHANT_NAMESPACE);

        $additionalImageUrls = $imageCandidates
            ->map(function (object $image): ?string {
                $attributes = (array) $image;

                return $attributes['large'] ?? $attributes['url'] ?? null;
            })
            ->filter(fn (?string $url): bool => ! in_array($url, [null, '', $imageUrl], true))
            ->unique()
            ->take(10);

        foreach ($additionalImageUrls as $additionalImageUrl) {
            $item->addChild(
                'g:additional_image_link',
                htmlspecialchars($additionalImageUrl, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                self::MERCHANT_NAMESPACE,
            );
        }
    }
}
