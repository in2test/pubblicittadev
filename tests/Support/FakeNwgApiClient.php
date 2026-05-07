<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\NwgApiClient;
use Override;

class FakeNwgApiClient extends NwgApiClient
{
    public function __construct() {}

    #[Override]
    public function getFullProductData(string $productNumber): ?array
    {
        return [
            'productName' => 'Test Product',
            'productCatalogText' => 'Test description',
            'retailPrice' => ['price' => 100],
            'pictures' => [],
            'variations' => [
                [
                    'itemColorCode' => '99',
                    'itemWebColor' => 'Blue',
                    'pictures' => [
                        [
                            'standardUrl' => 'https://example.com/variation-99.jpg',
                            'thumbnailUrl' => 'https://example.com/variation-99-thumb.jpg',
                            'largeThumbnailUrl' => 'https://example.com/variation-99-large.jpg',
                        ],
                    ],
                    'skus' => [
                        [
                            'availability' => 10,
                            'sku' => 'TEST-99-M',
                            'skuSize' => [
                                'webtext' => 'M',
                                'size' => 'M',
                            ],
                            'active' => true,
                        ],
                    ],
                ],
            ],
        ];
    }
}
