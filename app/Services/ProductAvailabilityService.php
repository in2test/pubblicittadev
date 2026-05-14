<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;

class ProductAvailabilityService
{
    private readonly NwgApiClient $apiClient;

    private readonly ProductDataMapper $dataMapper;

    private readonly ProductValidator $validator;

    private readonly ProductSynchronizer $synchronizer;

    public function __construct()
    {
        $this->apiClient = app(NwgApiClient::class);
        $this->dataMapper = app(ProductDataMapper::class);
        $this->validator = app(ProductValidator::class);
        $this->synchronizer = app(ProductSynchronizer::class);
    }

    /**
     * Get full product data including metadata and variations.
     */
    public function getFullProductData(string $productNumber): ?array
    {
        return $this->apiClient->getFullProductData($productNumber);
    }

    public function getBasicProductData(string $productNumber): ?array
    {
        return $this->apiClient->getBasicProductData($productNumber);
    }

    /**
     * Fetch basic info (name and price) for a SKU.
     * Useful for real-time verification in admin.
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
        ];
    }

    // Fetch full GraphQL payload using the exact provided query
    public function fetchFullGraphQLProductData(string $productNumber, string $language): ?array
    {
        return $this->apiClient->getFullProductData($productNumber);
    }

    // Map a full GraphQL payload to remote_images structure
    public function mapFullProductPayloadToRemoteImages(array $payload): array
    {
        return $this->dataMapper->mapFullProductPayloadToRemoteImages($payload);
    }

    /**
     * Validate multiple SKUs from the API.
     * Returns array with valid and invalid SKU codes.
     */
    public function validateSkus(array $skus): array
    {
        return $this->validator->validateSkus($skus);
    }

    /**
     * Synchronize product variations in the database with the API data.
     */
    public function syncProduct(Product $product): void
    {
        $this->synchronizer->syncProduct($product);
    }

    /**
     * Synchronize just the availability/quantities.
     */
    public function syncAvailability(Product $product): void
    {
        $this->synchronizer->syncAvailability($product);
    }
}
