<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Product;
use App\Models\VariationOption;

/**
 * The identity of a single item in the Google Merchant feed.
 *
 * Groups the values that describe the item itself, so the writer does not
 * need to receive them one by one.
 */
final readonly class MerchantFeedItem
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public string $link,
        public ?string $color = null,
        public ?string $size = null,
    ) {}

    /**
     * Build the item of a single product, without variants.
     */
    public static function forProduct(
        Product $product,
        string $description,
        string $link,
        ?string $color = null,
    ): self {
        return new self(
            id: $product->sku ?? '',
            title: (string) $product->name,
            description: $description,
            link: $link,
            color: $color,
        );
    }

    /**
     * Build the item of a single variant of a product.
     */
    public static function forVariant(
        Product $product,
        string $id,
        string $title,
        string $description,
        string $link,
        ?VariationOption $colorOption = null,
        ?VariationOption $sizeOption = null,
    ): self {
        return new self(
            id: $id,
            title: $title,
            description: $description,
            link: $link,
            color: $colorOption?->name,
            size: $sizeOption?->name,
        );
    }
}
