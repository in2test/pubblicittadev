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
     * Get full product data including metadata and variations.
     */
    public function getFullProductData(string $productNumber): ?array
    {
        $query = <<<'GRAPHQL'
query Query($productNumber: String!, $language:String!) {
  productById(productNumber: $productNumber, language: $language) {
    productName
    productCatalogText
    productBrand
    outlet
    retailPrice {
      price
    }
    pictures {
      highResUrl
    }
    variations {
      itemColorCode
      itemWebColor
      pictures {
        highResUrl
      }
      skus {
        sku
        availability
        active
        description
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
            if (!$response->successful()) {
                Log::error("NWG API Error (full GraphQL): {$response->status()} - {$response->body()}");
                return null;
            }
            return $response->json()['data']['productById'] ?? null;
        } catch (Exception $e) {
            Log::error("NWG API Exception (full GraphQL): {$e->getMessage()}");
            return null;
        }
    }

    // Map a full GraphQL payload to remote_images structure
    public function mapFullProductPayloadToRemoteImages(array $payload): array
    {
        $remoteImages = [];
        // top-level pictures
        if (! empty($payload['pictures'] ?? [])) {
            foreach ($payload['pictures'] as $idx => $img) {
                $url = $img['standardUrl'] ?? $img['largeThumbnailUrl'] ?? $img['thumbnailUrl'] ?? '';
                if (!$url) continue;
                $remoteImages[] = [
                    'id' => 'top_'.$idx,
                    'url' => $url,
                    'medium' => $img['largeThumbnailUrl'] ?? $img['standardUrl'] ?? '',
                    'thumb' => $img['thumbnailUrl'] ?? $img['largeThumbnailUrl'] ?? '',
                    'color_ids' => [],
                ];
            }
        }

        // variations
        foreach ($payload['variations'] ?? [] as $v) {
            $colorCode = $v['itemColorCode'] ?? '';
            if (! empty($v['pictures'])) {
                foreach ($v['pictures'] as $idx => $vImg) {
                    $url = $vImg['standardUrl'] ?? $vImg['largeThumbnailUrl'] ?? $vImg['thumbnailUrl'] ?? '';
                    if (!$url) continue;
                    $remoteImages[] = [
                        'id' => 'var_'.($colorCode ?: 'nc').'_'.$idx,
                        'url' => $url,
                        'medium' => $vImg['largeThumbnailUrl'] ?? $vImg['standardUrl'] ?? '',
                        'thumb' => $vImg['thumbnailUrl'] ?? $vImg['largeThumbnailUrl'] ?? '',
                        'color_ids' => [],
                    ];
                }
            }
        }
        return $remoteImages;
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

            $data = $this->getFullProductData($sku);
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
        $data = $this->getFullProductData($product->sku);

        if (! $data) {
            return;
        }

        $product->update(['sync_progress' => 10]);

        // For NewWave products, we cache/update the product metadata as well
        if ($product->type === Product::TYPE_NEWWAVE) {
            // Update basic info
            $updateData = [
                'name' => $data['productName'] ?? $product->name,
            ];

            if (! $product->override_price) {
                $updateData['price'] = $data['retailPrice']['price'] ?? $product->price;
            }

            if (! $product->override_description) {
                $updateData['description'] = $data['productCatalogText'] ?? $product->description;
            }

            $updateData['sync_progress'] = 20;
            $product->update($updateData);

            $existingMedia = $product->getMedia('images');

            // Sync Main Pictures
            if (! empty($data['pictures'])) {
                foreach (array_slice($data['pictures'], 0, 5) as $img) {
                    $url = $img['highResUrl'] ?? '';
                    if (! $url) {
                        continue;
                    }
                    $fileName = basename(parse_url((string) $url, PHP_URL_PATH));

                    if (! $existingMedia->contains('file_name', $fileName)) {
                        try {
                            $product->addMediaFromUrl($url)
                                ->withCustomProperties(['color_ids' => [], 'alt' => null, 'is_manual' => false])
                                ->toMediaCollection('images');
                        } catch (Exception $e) {
                            Log::warning("Failed to download main image for SKU {$product->sku}: {$e->getMessage()}");
                        }
                    }
                }
            }
        }

        $product->update(['sync_progress' => 40]);

        if (empty($data['variations'])) {
            return;
        }

        // 1. Pre-load all existing media once for lookup
        $existingMedia = $product->getMedia('images');
        $mediaLookup = $existingMedia->keyBy('file_name');

        // 2. Pre-load Colors and Sizes into local cache for 1-to-1 mapping
        $colorsCache = Color::all()->keyBy('color_code');
        $sizesCache = Size::all()->keyBy('size_code');

        $variationsToUpsert = [];
        $totalVariations = count($data['variations']);
        $processedVariations = 0;

        foreach ($data['variations'] as $variationData) {
            $processedVariations++;

            // Only update progress every 10 variations to reduce DB writes
            if ($processedVariations % 10 === 0 || $processedVariations === $totalVariations) {
                $progress = 40 + (int) (($processedVariations / max($totalVariations, 1)) * 55);
                $product->update(['sync_progress' => $progress]);
            }

            // Rest of the code...

            // Find or create Color for this variation
            $colorCode = (string) ($variationData['itemColorCode'] ?? '');
            $colorName = is_array($variationData['itemWebColor'] ?? null)
                ? ($variationData['itemWebColor'][0] ?? '')
                : (string) ($variationData['itemWebColor'] ?? '');
            $color = null;

            if ($colorCode !== '' && $colorCode !== '0') {
                $color = $colorsCache->get($colorCode);

                if (! $color) {
                    $color = Color::create([
                        'color_code' => $colorCode,
                        'color_name' => $colorName ?: 'Color '.$colorCode,
                        'color_hex' => '#CCCCCC',
                    ]);
                    $colorsCache->put($colorCode, $color);
                }
            }

            // Sync Variation Images associated with this color
            if ($product->type === Product::TYPE_NEWWAVE && ! empty($variationData['pictures']) && $color) {
                foreach ($variationData['pictures'] as $img) {
                    $url = $img['highResUrl'] ?? '';
                    if (! $url) {
                        continue;
                    }
                    $fileName = basename(parse_url((string) $url, PHP_URL_PATH));

                    $match = $mediaLookup->get($fileName);

                    if (! $match) {
                        try {
                            $newMedia = $product->addMediaFromUrl($url)
                                ->withCustomProperties(['color_ids' => [$color->id]])
                                ->toMediaCollection('images');

                            $mediaLookup->put($fileName, $newMedia);
                        } catch (Exception) {
                            // Silently skip
                        }
                    } else {
                        // If image exists but lacks this color ID, add it
                        $props = $match->custom_properties;
                        if (! ($props['is_manual'] ?? false)) {
                            $colorIds = (array) ($props['color_ids'] ?? []);
                            if (! in_array($color->id, $colorIds)) {
                                $colorIds[] = $color->id;
                                $props['color_ids'] = $colorIds;
                                $match->custom_properties = $props;
                                $match->save();
                            }
                        }
                    }
                }
            }

            if (empty($variationData['skus'])) {
                continue;
            }

            foreach ($variationData['skus'] as $item) {
                $actualAvailability = (int) $item['availability'];
                // Apply halving logic: floor(actual / 2)
                $halvedQuantity = (int) floor($actualAvailability / 2);

                // Find or create Size
                $sizeName = $item['skuSize']['webtext'] ?? null;
                $sizeCode = (string) ($item['skuSize']['size'] ?? '');
                $size = null;

                if ($sizeCode !== '') {
                    $size = $sizesCache->get($sizeCode);

                    if (! $size && $sizeName) {
                        $size = Size::create([
                            'size_code' => $sizeCode,
                            'size' => $sizeName,
                            'size_name' => $sizeName,
                        ]);
                        $sizesCache->put($sizeCode, $size);
                    }
                }

                if ($color && $size) {
                    $variationsToUpsert[] = [
                        'sku' => $item['sku'],
                        'product_id' => $product->id,
                        'color_id' => $color->id,
                        'size_id' => $size->id,
                        'quantity' => $halvedQuantity,
                        'is_available' => ($item['active'] ?? true) && $halvedQuantity > 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // 3. Batch upsert variations for high performance
        if ($variationsToUpsert !== []) {
            ProductVariation::upsert(
                $variationsToUpsert,
                ['sku'],
                ['color_id', 'size_id', 'quantity', 'is_available', 'updated_at']
            );
        }

        // Re-load variations
        $product->load([
            'variations.color',
            'variations.size',
        ]);
    }
}
