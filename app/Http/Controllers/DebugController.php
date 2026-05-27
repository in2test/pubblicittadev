<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebugController extends Controller
{
    public function remoteImages(string $sku, Request $request): JsonResponse
    {
        $product = Product::where('sku', '=', $sku, 'and')->first();
        if (! $product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json([
            'sku' => $sku,
            'images_table' => $product->images,
            'all_images' => $product->getAllImages(),
        ]);
    }

    public function remoteImagesFromPayload(string $sku, Request $request): JsonResponse
    {
        $language = $request->query('language', 'it');
        /** @var ProductAvailabilityService $service */
        $service = app(ProductAvailabilityService::class);
        $payload = $service->fetchFullGraphQLProductData($sku, $language);
        if (! $payload) {
            return response()->json(['error' => 'Full payload not available'], 404);
        }
        $remote = $service->mapFullProductPayloadToRemoteImages($payload);

        return response()->json(['sku' => $sku, 'mapped_remote_images' => $remote]);
    }

    public function mapPayloadToRemoteImages(string $sku, Request $request): JsonResponse
    {
        $language = $request->query('language', 'it');
        /** @var ProductAvailabilityService $service */
        $service = app(ProductAvailabilityService::class);
        $payload = $service->fetchFullGraphQLProductData($sku, $language);
        if (! $payload) {
            return response()->json(['error' => 'Full payload not available'], 404);
        }
        $remote = $service->mapFullProductPayloadToRemoteImages($payload);
        $product = Product::where('sku', '=', $sku, 'and')->first();
        if (! $product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Persist to images table instead of JSON column
        foreach ($remote as $img) {
            Image::updateOrCreate([
                'product_id' => $product->id,
                'image_url' => $img['url'],
            ], [
                'image_description' => $product->name,
                'variation_option_id' => $img['variation_option_ids'][0] ?? null,
                'thumbnail_url' => $img['thumb'] ?? null,
                'medium_url' => $img['medium'] ?? null,
                'large_url' => $img['large'] ?? null,
            ]);
        }

        return response()->json(['sku' => $sku, 'images_added_to_table' => count($remote)]);
    }

    public function forceMapAll(string $sku, Request $request): JsonResponse
    {
        $language = $request->query('language', 'it');
        /** @var ProductAvailabilityService $service */
        $service = app(ProductAvailabilityService::class);
        $payload = $service->fetchFullGraphQLProductData($sku, $language);
        if (! $payload) {
            return response()->json(['error' => 'Full payload not available'], 404);
        }
        $remote = $service->mapFullProductPayloadToRemoteImages($payload);
        $product = Product::where('sku', '=', $sku, 'and')->first();
        if (! $product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        foreach ($remote as $img) {
            Image::updateOrCreate([
                'product_id' => $product->id,
                'image_url' => $img['url'],
            ], [
                'image_description' => $product->name,
                'variation_option_id' => $img['variation_option_ids'][0] ?? null,
                'thumbnail_url' => $img['thumb'] ?? null,
                'medium_url' => $img['medium'] ?? null,
                'large_url' => $img['large'] ?? null,
            ]);
        }

        return response()->json(['sku' => $sku, 'images_added_to_table' => count($remote)]);
    }

    public function apiData(string $sku, Request $request): JsonResponse
    {
        $language = $request->query('language', 'it');
        /** @var ProductAvailabilityService $service */
        $service = app(ProductAvailabilityService::class);
        $data = $service->getFullProductData($sku);
        if (! $data) {
            return response()->json(['error' => 'Data not found or API error'], 404);
        }

        return response()->json(['sku' => $sku, 'language' => $language, 'data' => $data]);
    }

    public function apiDataFull(string $sku, Request $request): JsonResponse
    {
        $language = $request->query('language', 'it');
        /** @var ProductAvailabilityService $service */
        $service = app(ProductAvailabilityService::class);
        $data = $service->getFullProductData($sku);
        if (! $data) {
            return response()->json(['error' => 'Data not found or API error'], 404);
        }

        return response()->json(['sku' => $sku, 'language' => $language, 'data' => $data]);
    }
}
