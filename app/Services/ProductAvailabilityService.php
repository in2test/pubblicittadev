<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Size;
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
     * Get basic metadata for a product.
     */
    public function getBaseMetadata(string $productNumber): ?array
    {
        $query = <<<'GRAPHQL'
query Query($productNumber: String!, $language: String!) {
  productById(productNumber: $productNumber, language: $language) {
    productNumber
    productName
    description
    pictures {
      imageUrl
      thumbnailUrl
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
                return null;
            }

            return $response->json()['data']['productById'] ?? null;
        } catch (\Exception $e) {
            Log::error("NWG API Exception (Metadata): {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Get full details for variations.
     */
    public function getVariationDetails(string $productNumber): array
    {
        $query = <<<'GRAPHQL'
query Query($productNumber: String) {
  allSkusByProductNumber(productNumber: $productNumber) {
    sku
    availability
    active
    description
    skucolor
    skuSize {
      webtext
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
                    ],
                ]);

            if (! $response->successful()) {
                Log::error("NWG API Error: {$response->status()} - {$response->body()}");

                return [];
            }

            $data = $response->json();

            return $data['data']['allSkusByProductNumber'] ?? [];
        } catch (\Exception $e) {
            Log::error("NWG API Exception: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * Fetch basic info (name and price) for a SKU.
     * Useful for real-time verification in admin.
     */
    public function fetchBasicInfo(string $productNumber): ?array
    {
        $metadata = $this->getBaseMetadata($productNumber);
        $variations = $this->getVariationDetails($productNumber);

        if (! $metadata && empty($variations)) {
            return null;
        }

        return [
            'name' => $metadata['productName'] ?? ($variations[0]['description'] ?? null),
            'price' => ! empty($variations) ? $variations[0]['retailPrice']['price'] : null,
            'description' => $metadata['description'] ?? null,
        ];
    }

    /**
     * Synchronize product variations in the database with the API data.
     */
    public function syncProduct(Product $product): void
    {
        $metadata = $this->getBaseMetadata($product->sku);
        $details = $this->getVariationDetails($product->sku);

        if (empty($details)) {
            return;
        }

        // For NewWave products, we cache/update the product metadata as well
        if ($product->type === Product::TYPE_NEWWAVE) {
            $productName = $metadata['productName'] ?? $this->extractProductName($details);
            $firstItem = $details[0];

            // Update basic info
            $product->update([
                'name' => $productName ?: ($firstItem['description'] ?? $product->name),
                'price' => $firstItem['retailPrice']['price'] ?? $product->price,
                'description' => $metadata['description'] ?? $product->description,
            ]);

            // Sync Images if empty
            if ($product->getMedia('images')->isEmpty() && ! empty($metadata['pictures'])) {
                foreach (array_slice($metadata['pictures'], 0, 10) as $img) {
                    try {
                        $product->addMediaFromUrl($img['imageUrl'])
                            ->toMediaCollection('images');
                    } catch (\Exception $e) {
                        Log::warning("Failed to download image for SKU {$product->sku}: {$e->getMessage()}");
                    }
                }
            }
        }

        foreach ($details as $item) {
            $actualAvailability = (int) $item['availability'];
            // Apply halving logic: floor(actual / 2)
            $halvedQuantity = (int) floor($actualAvailability / 2);

            // Find or create Color
            $colorCode = (string) ($item['skucolor'] ?? '');
            $color = null;
            if ($colorCode) {
                $color = Color::firstOrCreate(
                    ['color_code' => $colorCode],
                    ['color_name' => $this->guessColorNameFromDescription($item['description'], $colorCode)]
                );
            }

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
                        'is_available' => $item['active'] && $halvedQuantity > 0,
                    ]
                );
            }
        }

        // Re-load variations
        $product->load([
            'variations.color',
            'variations.size',
        ]);
    }

    /**
     * Extract a clean product name by finding the common prefix of all SKU descriptions.
     */
    private function extractProductName(array $details): string
    {
        $descriptions = collect($details)->pluck('description')->filter()->values();

        if ($descriptions->isEmpty()) {
            return '';
        }

        $prefix = $descriptions[0];
        $len = strlen($prefix);

        foreach ($descriptions as $string) {
            while ($len > 0 && strpos($string, substr($prefix, 0, $len)) !== 0) {
                $len--;
            }
            if ($len === 0) {
                break;
            }
        }

        return trim(substr($prefix, 0, $len));
    }

    /**
     * Try to guess color name from description (e.g. "SANDERS JACKET ROSSO S" -> "ROSSO")
     */
    private function guessColorNameFromDescription(string $description, string $code): string
    {
        // This is a heuristic. Usually it's Product + Color + Size
        $parts = explode(' ', $description);
        if (count($parts) >= 3) {
            // Take the part before the last one (size)
            return $parts[count($parts) - 2];
        }

        return 'Color '.$code;
    }
}
