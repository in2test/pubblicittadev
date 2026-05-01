<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Size;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductAvailabilityService
{
    protected string $endpoint;

    protected string $token;

    public function __construct()
    {
        $this->endpoint = (string) config('services.nwg.endpoint');
        $this->token = (string) config('services.nwg.token');
    }

    /**
     * Fetch basic info (name and price) for a SKU.
     * Useful for real-time verification in admin.
     */
    public function getBasicProductData(string $productNumber): ?array
    {
        $query = <<<'GRAPHQL'
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

        try {
            $response = Http::withoutVerifying()
                ->withToken($this->token)
                ->post($this->endpoint, [
                    'query' => $query,
                    'variables' => [
                        'productNumber' => $productNumber,
                        'language' => 'it',
                    ],
                ]);

            if (! $response->successful()) {
                Log::error("NWG API Error: {$response->status()} - {$response->body()}");

                return null;
            }

            return $response->json()['data']['productById'] ?? null;
        } catch (Exception $e) {
            Log::error("NWG API Exception: {$e->getMessage()}");

            return null;
        }
    }
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
            'images' => array_map(function ($pic) {
                return [
                    'thumbnail' => $pic['thumbnailUrl'] ?? null,
                    'largeThumbnail' => $pic['largeThumbnailUrl'] ?? null,
                    'standard' => $pic['standardUrl'] ?? null,
                    'type' => $pic['resourcePictureType'] ?? null,
                    'fileId' => $pic['resourceFileId'] ?? null,
                ];
            }, $data['pictures'] ?? []),
        ];
    }

    // Fetch full GraphQL payload using the exact provided query
    /**
     * Validate multiple SKUs from the API.
     * Returns array with valid and invalid SKU codes.
     */
    public function getFullProductData(string $productNumber): ?array
    {
        $query = <<<'GRAPHQL'
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

        try {
            $response = Http::withoutVerifying()
                ->withToken($this->token)
                ->post($this->endpoint, [
                    'query' => $query,
                    'variables' => [
                        'productNumber' => $productNumber,
                        'language' => 'it',
                    ],
                ]);

            if (! $response->successful()) {
                Log::error("NWG API Error: {$response->status()} - {$response->body()}");

                return null;
            }

            return $response->json()['data']['productById'] ?? null;
        } catch (Exception $e) {
            Log::error("NWG API Exception: {$e->getMessage()}");

            return null;
        }
    }
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
            'images' => array_map(function ($pic) {
                return [
                    'thumbnail' => $pic['thumbnailUrl'] ?? null,
                    'largeThumbnail' => $pic['largeThumbnailUrl'] ?? null,
                    'standard' => $pic['standardUrl'] ?? null,
                    'type' => $pic['resourcePictureType'] ?? null,
                    'fileId' => $pic['resourceFileId'] ?? null,
                ];
            }, $data['pictures'] ?? []),
            'variations' => array_map(function ($variation) {
                return [
                    'colorCode' => $variation['itemColorCode'] ?? null,
                    'colorName' => $variation['itemWebColor'] ?? null,
                    'images' => array_map(function ($pic) {
                        return [
                            'thumbnail' => $pic['thumbnailUrl'] ?? null,
                            'largeThumbnail' => $pic['largeThumbnailUrl'] ?? null,
                            'standard' => $pic['standardUrl'] ?? null,
                        ];
                    }, $variation['pictures'] ?? []),
                    'skus' => array_map(function ($sku) {
                        return [
                            'sizeValue' => $sku['skuSize']['size'] ?? null,
                            'sizeWebText' => $sku['skuSize']['webtext'] ?? null,
                            'availability' => $sku['availability'] ?? null,
                        ];
                    }, $variation['skus'] ?? []),
                ];
            }, $data['variations'] ?? []),
        ];
    }
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
     * Synchronize product variations in the database with the API data.
     */
    public function syncProduct(Product $product): void
    {
        $data = $this->fetchFullInfo($product->sku);
        if (! $data) {
            throw new Exception("Failed to fetch data for SKU {$product->sku}");
        }

        $product->update([
            'name' => $data['name'] ?? $product->name,
            'price' => $data['price'] ?? $product->price,
            'description' => $data['description'] ?? $product->description,
            'brand' => $data['brand'] ?? $product->brand,
            'gender' => $data['gender'] ?? $product->gender,
        ]); 
        Log::info("Updated product {$product->sku} with basic info from NWG API.");
        $existingVariations = $product->variations()->get()->keyBy('color_code');
        $apiVariations = collect($data['variations'] ?? [])->keyBy('colorCode');

        // Update or create variations
        foreach ($apiVariations as $colorCode => $variationData) {
            $variation = $existingVariations->get($colorCode);
            if ($variation) {
                $variation->update([
                    'color_name' => $variationData['colorName'] ?? $variation->color_name,
                    
                ]);
                Log::info("Updated variation {$colorCode} for product {$product->sku}.");
            } else {
                $variation = $product->variations()->create([
                    'color_code' => $colorCode,
                    'color_name' => $variationData['colorName'] ?? null,
                ]);
                Log::info("Created variation {$colorCode} for product {$product->sku}.");
            }

            // Sync sizes and availability
            $existingSizes = $variation->sizes()->get()->keyBy('size_value');
            $apiSizes = collect($variationData['skus'] ?? [])->keyBy('sizeValue');

            foreach ($apiSizes as $sizeValue => $sizeData) {
                $size = $existingSizes->get($sizeValue);
                if ($size) {
                    $size->update([
                        'web_text' => $sizeData['sizeWebText'] ?? $size->web_text,
                        'is_available' => ($sizeData['availability'] ?? false) === true,
                    ]);
                } else {
                    $variation->sizes()->create([
                        'size_value' => $sizeValue,
                        'web_text' => $sizeData['sizeWebText'] ?? null,
                        'is_available' => ($sizeData['availability'] ?? false) === true,
                    ]);
                }
            }

            // Optionally, handle images for variations here
        }

        // Optionally, remove variations/sizes that no longer exist in the API
        foreach ($existingVariations as $colorCode => $variation) {
            if (! $apiVariations->has($colorCode)) {
                $variation->delete();
            } else {
                $apiSizes = collect($apiVariations[$colorCode]['skus'] ?? [])->pluck('sizeValue')->all();
                foreach ($variation->sizes as $size) {
                    if (! in_array($size->size_value, $apiSizes, true)) {
                        $size->delete();
                    }
                }
            }
        }

        // Best-effort: mark as synced after completing sync pass
        try {
            if (class_exists(\App\Enums\SyncStatus::class)) {
                $product->update(['sync_status' => \App\Enums\SyncStatus::Synced, 'synced_at' => now()]);
            }
        } catch (\Throwable $e) {
            Log::warning('NWG sync status update failed: '.$e->getMessage());
        }

    }

        
    
}
