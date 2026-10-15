<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\VariationOption;
use SimpleXMLElement;

/**
 * Writes individual item XML nodes into a Google Merchant Feed channel.
 *
 * Responsible for the XML shape of a single feed item: identifiers,
 * titles, descriptions, image links, pricing fields, and category
 * taxonomy — nothing that requires fetching data from the database.
 */
class MerchantFeedItemWriter
{
    private const string MERCHANT_NAMESPACE = 'http://base.google.com/ns/1.0';

    public function addItemDetails(
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

    public function addCategoryAndDemographics(
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
}
