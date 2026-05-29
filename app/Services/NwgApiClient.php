<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NwgApiClient
{
    protected string $endpoint;

    protected string $token;

    public function __construct()
    {
        $this->endpoint = (string) config('services.nwg.endpoint');
        $this->token = (string) config('services.nwg.token');
    }

    /**
     * Get full product data including metadata and variations.
     *
     * @return array<string, mixed>|null
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
                        sku
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

    /**
     * @return array<string, mixed>|null
     */
    public function getBasicProductData(string $productNumber): ?array
    {
        $query = <<<'GRAPHQL'
            query Query($productNumber: String!, $language:String!) {
                productById(productNumber: $productNumber, language: $language) {
                    productName
                    productCatalogText
                    productBrand
                    productGender {
                        key
                        value
                    }
                    retailPrice {
                        price
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

    /**
     * @return array<string, mixed>|null
     */
    public function getProductAvailability(string $productNumber): ?array
    {
        $query = <<<'GRAPHQL'
            query getProductAvailability($productNumber: String!, $language: String!) {
                productById(productNumber: $productNumber, language: $language) {
                    variations {
                        skus {
                            availability
                            sku
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

    /**
     * Fetch full GraphQL payload using the exact provided query
     *
     * @return array<string, mixed>|null
     */
    public function fetchFullGraphQLProductData(string $productNumber, string $language): ?array
    {
        $query = <<<'GQL'
query Query($productNumber: String!, $language: String!) {
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
GQL;

        try {
            $response = Http::withoutVerifying()
                ->withToken($this->token)
                ->post($this->endpoint, [
                    'query' => $query,
                    'variables' => [
                        'productNumber' => $productNumber,
                        'language' => $language,
                    ],
                ]);
            if (! $response->successful()) {
                Log::error("NWG API Error (full GraphQL): {$response->status()} - {$response->body()}");

                return null;
            }

            return $response->json()['data']['productById'] ?? null;
        } catch (Exception $e) {
            Log::error("NWG API Exception (full GraphQL): {$e->getMessage()}");

            return null;
        }
    }
}
