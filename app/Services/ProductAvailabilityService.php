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
        ];
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

            $product->update($updateData);

            $existingMedia = $product->getMedia('images');

            // Sync Main Pictures
            if (! empty($data['pictures'])) {
                foreach (array_slice($data['pictures'], 0, 5) as $img) {
                    $url = $img['imageSource'] ?? '';
                    if (! $url) {
                        continue;
                    }
                    $fileName = basename(parse_url((string) $url, PHP_URL_PATH));

                    if (! $existingMedia->contains('file_name', $fileName)) {
                        try {
                            $product->addMediaFromUrl($url)
                                ->toMediaCollection('images');
                        } catch (Exception $e) {
                            Log::warning("Failed to download main image for SKU {$product->sku}: {$e->getMessage()}");
                        }
                    }
                }
            }
        }

        if (empty($data['variations'])) {
            return;
        }

        foreach ($data['variations'] as $variationData) {
            // Find or create Color for this variation
            $colorCode = (string) ($variationData['itemColorCode'] ?? '');
            $colorName = is_array($variationData['itemWebColor'] ?? null)
                ? ($variationData['itemWebColor'][0] ?? '')
                : (string) ($variationData['itemWebColor'] ?? '');
            $color = null;

            if ($colorCode !== '' && $colorCode !== '0') {
                $color = Color::firstOrCreate(
                    ['color_code' => $colorCode],
                    ['color_name' => $colorName ?: 'Color '.$colorCode]
                );
            }

            // Sync Variation Images associated with this color
            if ($product->type === Product::TYPE_NEWWAVE && ! empty($variationData['pictures']) && $color) {
                $existingMedia = $product->getMedia('images'); // Refresh list
                foreach ($variationData['pictures'] as $img) {
                    $url = $img['highResUrl'] ?? '';
                    if (! $url) {
                        continue;
                    }
                    $fileName = basename(parse_url((string) $url, PHP_URL_PATH));

                    $match = $existingMedia->firstWhere('file_name', $fileName);

                    if (! $match) {
                        try {
                            $product->addMediaFromUrl($url)
                                ->withCustomProperties(['color_ids' => [$color->id]])
                                ->toMediaCollection('images');
                        } catch (Exception) {
                            // Silently skip
                        }
                    } else {
                        // If image exists but lacks this color ID, add it
                        // ONLY if it's not manually managed
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
                $size = null;
                if ($sizeName) {
                    $size = Size::firstOrCreate(
                        ['size' => $sizeName],
                        ['size_name' => $sizeName]
                    );
                }

                if ($color && $size) {
                    ProductVariation::updateOrCreate(
                        [
                            'sku' => $item['sku'],
                            'product_id' => $product->id,
                        ],
                        [
                            'color_id' => $color->id,
                            'size_id' => $size->id,
                            'quantity' => $halvedQuantity,
                            'is_available' => ($item['active'] ?? true) && $halvedQuantity > 0,
                        ]
                    );
                }
            }
        }

        // Re-load variations
        $product->load([
            'variations.color',
            'variations.size',
        ]);
    }
}
