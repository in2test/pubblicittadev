<?php

declare(strict_types=1);

namespace App\Services;

class ProductValidator
{
    private NwgApiClient $apiClient;

    public function __construct(NwgApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Validate multiple SKUs from the API.
     * Returns array with valid and invalid SKU codes.
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

            $data = $this->apiClient->getFullProductData($sku);
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
}
