<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SyncStatus;
use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductAvailabilityService
{
    private const string BASIC_PRODUCT_QUERY = <<<'GRAPHQL'
        query getBasicProductInfo($productNumber: String!, $language: String!) {
            productById(productNumber: $productNumber, language: $language) {
                productName
                productCatalogText
                productBrand
                productGender {
                    value
                    key
                }
                retailPrice {
                    price
                }
                pictures {
                    thumbnailUrl
                    largeThumbnailUrl
                    standardUrl
                    resourcePictureType
                    resourceFileId
                }
            }
        }
    GRAPHQL;

    private const string FULL_PRODUCT_QUERY = <<<'GRAPHQL'
        query getFullProductInfo($productNumber: String!, $language: String!) {
            productById(productNumber: $productNumber, language: $language) {
                productName
                productCatalogText
                productBrand
                productGender {
                    value
                    key
                }
                retailPrice {
                    price
                }
                pictures {
                    thumbnailUrl
                    largeThumbnailUrl
                    standardUrl
                }
                variations {
                    itemColorCode
                    itemWebColor
                    pictures {
                        thumbnailUrl
                        largeThumbnailUrl
                        standardUrl
                    }
                    skus {
                        availability
                        skuSize {
                            webtext
                            size
                        }
                    }
                }
            }
        }
    GRAPHQL;

    protected string $endpoint;

    protected string $token;

    public function __construct()
    {
        $this->endpoint = (string) config('services.nwg.endpoint');
        $this->token = (string) config('services.nwg.token');
    }

    /**
     * Send a GraphQL request to the external NWG API.
     *
     * Returns the decoded response payload or null when the request fails.
     */
    protected function executeGraphQlQuery(string $query, array $variables): ?array
    {
        try {
            $response = Http::withoutVerifying()
                ->withToken($this->token)
                ->post($this->endpoint, [
                    'query' => $query,
                    'variables' => $variables,
                ]);

            if (! $response->successful()) {
                Log::error("NWG API Error: {$response->status()} - {$response->body()}");

                return null;
            }

            return $response->json()['data']['productById'] ?? null;
        } catch (Exception $exception) {
            Log::error("NWG API Exception: {$exception->getMessage()}");

            return null;
        }
    }

    /**
     * Return the raw API payload for the requested SKU.
     */
    protected function getBasicProductData(string $productNumber): ?array
    {
        return $this->executeGraphQlQuery(self::BASIC_PRODUCT_QUERY, [
            'productNumber' => $productNumber,
            'language' => 'it',
        ]);
    }

    /**
     * Return a simplified product payload for the admin form.
     */
    public function fetchBasicInfo(string $productNumber): ?array
    {
        $data = $this->getBasicProductData($productNumber);

        if (! $data) {
            return null;
        }

        return [
            'name' => $data['productName'] ?? null,
            'price' => $data['retailPrice']['price'] ?? null,
            'description' => $data['productCatalogText'] ?? null,
            'brand' => $data['productBrand'] ?? null,
            'gender' => $data['productGender']['value'] ?? null,
            'images' => array_map($this->mapPicture(...), $data['pictures'] ?? []),
        ];
    }

    /**
     * Return the raw API payload for the requested SKU including variations.
     */
    public function getFullProductData(string $productNumber): ?array
    {
        return $this->executeGraphQlQuery(self::FULL_PRODUCT_QUERY, [
            'productNumber' => $productNumber,
            'language' => 'it',
        ]);
    }

    /**
     * Return a simplified, normalized payload with product variations.
     */
    public function fetchFullInfo(string $productNumber): ?array
    {
        $data = $this->getFullProductData($productNumber);

        if (! $data) {
            return null;
        }

        return [
            'name' => $data['productName'] ?? null,
            'price' => $data['retailPrice']['price'] ?? null,
            'description' => $data['productCatalogText'] ?? null,
            'brand' => $data['productBrand'] ?? null,
            'gender' => $data['productGender']['value'] ?? null,
            'images' => array_map($this->mapPicture(...), $data['pictures'] ?? []),
            'variations' => array_map($this->mapVariation(...), $data['variations'] ?? []),
        ];
    }

    protected function mapPicture(array $picture): array
    {
        return [
            'thumbnail' => $picture['thumbnailUrl'] ?? null,
            'largeThumbnail' => $picture['largeThumbnailUrl'] ?? null,
            'standard' => $picture['standardUrl'] ?? null,
            'type' => $picture['resourcePictureType'] ?? null,
            'fileId' => $picture['resourceFileId'] ?? null,
        ];
    }

    protected function mapVariation(array $variation): array
    {
        return [
            'colorCode' => $variation['itemColorCode'] ?? null,
            'colorName' => $variation['itemWebColor'] ?? null,
            'images' => array_map($this->mapPicture(...), $variation['pictures'] ?? []),
            'skus' => array_map($this->mapSku(...), $variation['skus'] ?? []),
        ];
    }

    protected function mapSku(array $sku): array
    {
        return [
            'sizeValue' => $sku['skuSize']['size'] ?? null,
            'sizeWebText' => $sku['skuSize']['webtext'] ?? null,
            'availability' => $sku['availability'] ?? null,
        ];
    }

    public function mapFullProductPayloadToRemoteImages(array $payload): array
    {
        $images = [];
        $seenUrls = [];

        if (! empty($payload['pictures'])) {
            foreach ($payload['pictures'] as $pic) {
                $url = $pic['standardUrl'] ?? $pic['largeThumbnailUrl'] ?? $pic['thumbnailUrl'] ?? null;
                if ($url && ! in_array($url, $seenUrls)) {
                    $images[(string) Str::uuid()] = [
                        'url' => $url,
                        'thumb' => $pic['thumbnailUrl'] ?? $url,
                        'medium' => $pic['largeThumbnailUrl'] ?? $url,
                        'color_ids' => [],
                    ];
                    $seenUrls[] = $url;
                }
            }
        }

        if (! empty($payload['variations'])) {
            foreach ($payload['variations'] as $var) {
                if (empty($var['pictures'])) {
                    continue;
                }

                $colorCode = $var['itemColorCode'] ?? null;
                $colorId = null;
                if ($colorCode) {
                    $color = Color::query()->where('color_code', $colorCode)->first();
                    if ($color) {
                        $colorId = $color->id;
                    }
                }

                foreach ($var['pictures'] as $pic) {
                    $url = $pic['standardUrl'] ?? $pic['largeThumbnailUrl'] ?? $pic['thumbnailUrl'] ?? null;
                    if ($url) {
                        $existingKey = array_search($url, array_column(array_values($images), 'url'));
                        if ($existingKey !== false) {
                            $actualKey = array_keys($images)[$existingKey];
                            if ($colorId && ! in_array($colorId, $images[$actualKey]['color_ids'])) {
                                $images[$actualKey]['color_ids'][] = $colorId;
                            }
                        } else {
                            $images[(string) Str::uuid()] = [
                                'url' => $url,
                                'thumb' => $pic['thumbnailUrl'] ?? $url,
                                'medium' => $pic['largeThumbnailUrl'] ?? $url,
                                'color_ids' => $colorId ? [$colorId] : [],
                            ];
                            $seenUrls[] = $url;
                        }
                    }
                }
            }
        }

        return $images;
    }

    /**
     * Validate a list of SKU codes by checking whether each one exists in the NWG API.
     */
    public function validateSkus(array $skus): array
    {
        $valid = [];
        $invalid = [];

        foreach ($skus as $sku) {
            $sku = trim((string) $sku);
            if ($sku === '') {
                continue;
            }
            if ($sku === '0') {
                continue;
            }

            $data = $this->getBasicProductData($sku);

            if ($data && ! empty($data['productName'])) {
                $valid[$sku] = [
                    'name' => $data['productName'],
                    'price' => $data['retailPrice']['price'] ?? null,
                ];
            } else {
                $invalid[] = $sku;
            }
        }

        return ['valid' => $valid, 'invalid' => $invalid];
    }

    /**
     * Synchronize a NewWave product using the NWG API data.
     *
     * TODO: Implement manual image conversion triggering after sync.
     * Currently, images are stored as external URLs without downloading or converting.
     */
    public function syncProduct(Product $product): void
    {
        Log::info("NWG-SYNC-START: product_id={$product->id} sku={$product->sku}");

        $start = microtime(true);

        try {
            $remote = $this->fetchFullInfo($product->sku);

            if (! $remote) {
                throw new Exception("Failed to fetch data for SKU {$product->sku}");
            }

            $this->syncProductAttributes($product, $remote);
            $this->updateSyncProgress($product, 20);

            try {
                $this->syncVariations($product, $remote['variations'] ?? []);
            } catch (Exception $variationException) {
                Log::warning("NWG-SYNC-VARIATIONS-FAILED: product_id={$product->id} sku={$product->sku} error={$variationException->getMessage()}");
                // Continue with sync even if variations fail
            }

            $this->updateSyncProgress($product, 90);

            $product->fill([
                'sync_status' => SyncStatus::Synced,
                'synced_at' => now(),
            ])->save();
        } catch (Exception $exception) {
            Log::error("NWG-SYNC-EXCEPTION: {$exception->getMessage()}", ['exception' => $exception]);
            $product->fill(['sync_status' => SyncStatus::Failed, 'sync_progress' => 0])->save();
        } finally {
            $elapsed = (int) ((microtime(true) - $start) * 1000);
            Log::info("NWG-SYNC-END: product_id={$product->id} sku={$product->sku} elapsed={$elapsed}ms");
        }
    }

    protected function syncProductAttributes(Product $product, array $data): void
    {
        $product->fill([
            'name' => $data['name'] ?? $product->name,
            'price' => $data['price'] ?? $product->price,
            'description' => $data['description'] ?? $product->description,
            'brand' => $data['brand'] ?? $product->brand,
            'gender' => $data['gender'] ?? $product->gender,
            'external_image_urls' => $this->extractImageUrls($data['images'] ?? []),
        ])->save();
    }

    protected function syncVariations(Product $product, array $variations): void
    {
        $totalVariations = count($variations);

        foreach ($variations as $index => $variationData) {
            $this->syncVariation($product, $variationData);

            if ($totalVariations === 0) {
                continue;
            }

            $progress = 20 + (int) round((($index + 1) / $totalVariations) * 60);
            $this->updateSyncProgress($product, min($progress, 80));
        }

        if ($totalVariations === 0) {
            $this->updateSyncProgress($product, 80);
        }
    }

    protected function updateSyncProgress(Product $product, int $progress): void
    {
        $product->fill(['sync_progress' => $progress])->save();
    }

    protected function extractImageUrls(array $images): array
    {
        $urls = [];
        foreach ($images as $image) {
            if (! empty($image['thumbnail'])) {
                $urls[] = $image['thumbnail'];
            }
        }

        return $urls;
    }

    protected function syncVariation(Product $product, array $variationData): void
    {
        $colorCode = $variationData['colorCode'] ?? null;

        if ($colorCode === null) {
            return;
        }

        $color = Color::firstOrCreate(
            ['color_code' => $colorCode],
            ['color_name' => $variationData['colorName'] ?? 'Unknown']
        );

        foreach ($variationData['skus'] ?? [] as $skuData) {
            $sizeValue = $skuData['sizeValue'] ?? null;

            if ($sizeValue === null) {
                continue;
            }

            $size = Size::firstOrCreate(
                ['size_code' => $sizeValue],
                ['size_name' => $skuData['sizeWebText'] ?? null]
            );

            $product->variations()->firstOrCreate(
                ['color_id' => $color->id, 'size_id' => $size->id],
                [
                    'sku' => strtoupper("{$product->sku}-{$colorCode}-{$sizeValue}"),
                    'quantity' => (int) ($skuData['availability'] ?? 0),
                    'is_available' => (int) ($skuData['availability'] ?? 0) > 0,
                ]
            );
        }
    }
}
