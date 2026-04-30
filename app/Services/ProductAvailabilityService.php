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
        // If global NWG sync is turned off, do not call external NWG API
        if (filter_var(env('NWG_SYNC_OFF', 'false'), FILTER_VALIDATE_BOOLEAN)) {
            Log::info("NWG_SYNC_OFF: getFullProductData skipped for product {$productNumber}");
            return null;
        }
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

    /**
     * Fetch basic info (name and price) for a SKU.
     * Useful for real-time verification in admin.
     */
    public function fetchBasicInfo(string $productNumber): ?array
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
            'gender' => null,
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

        // Global guard: optionally disable automatic NWG sync on product creation
        $nwgSyncOff = filter_var(env('NWG_SYNC_OFF', 'false'), FILTER_VALIDATE_BOOLEAN);
        if ($nwgSyncOff) {
            Log::info("NWG_SYNC_OFF is enabled; skipping NWG sync for product id {$product->id}");
            return;
        }

        $product->update(['sync_progress' => 10]);

        // For NewWave products, switch to remote_images as canonical source (CDN-first)
        // Respect NWG_SYNC_OFF to skip the import of remote images as local media
        $nwgSyncOffLocal = filter_var(env('NWG_SYNC_OFF', 'false'), FILTER_VALIDATE_BOOLEAN);
        if ($product->type === Product::TYPE_NEWWAVE) {
            if ($nwgSyncOffLocal) {
                $product->update(['sync_progress' => 60]);
            } else {
                $remoteImages = $this->mapFullProductPayloadToRemoteImages($data);
                $product->update(['remote_images' => $remoteImages, 'sync_progress' => 60]);
            }
        }

        $product->update(['sync_progress' => 40]);

        if (empty($data['variations'])) {
            return;
        }

            // 1. Pre-load all existing media once for lookup (for non-NewWave imports we may still use local media)
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

            // (Note: Removed per-variation image downloads to use remote_images instead)

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
