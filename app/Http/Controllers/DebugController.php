<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use Illuminate\Http\Request;

class DebugController extends Controller
{
    public function remoteImages(string $sku, Request $request)
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

    public function remoteImagesFromPayload(string $sku, Request $request)
    {
        $language = $request->query('language', 'it');
        /** @var ProductAvailabilityService $service */
        $service = app(ProductAvailabilityService::class);
        $payload = $service->fetchFullGraphQLProductData($sku, $language);
        if (! $payload) {
            return response()->json(['error' => 'Full payload not available'], 404);
        }
        $remote = method_exists($service, 'mapFullProductPayloadToRemoteImages') ? $service->mapFullProductPayloadToRemoteImages($payload) : [];

        return response()->json(['sku' => $sku, 'mapped_remote_images' => $remote]);
    }

    public function mapPayloadToRemoteImages(string $sku, Request $request)
    {
        $language = $request->query('language', 'it');
        /** @var ProductAvailabilityService $service */
        $service = app(ProductAvailabilityService::class);
        $payload = method_exists($service, 'fetchFullGraphQLProductData') ? $service->fetchFullGraphQLProductData($sku, $language) : null;
        if (! $payload) {
            return response()->json(['error' => 'Full payload not available'], 404);
        }
        $remote = method_exists($service, 'mapFullProductPayloadToRemoteImages') ? $service->mapFullProductPayloadToRemoteImages($payload) : [];
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
                'color_id' => $img['color_id'] ?? null,
                'thumbnail_url' => $img['thumb'] ?? null,
                'medium_url' => $img['medium'] ?? null,
                'large_url' => $img['large'] ?? null,
            ]);
        }

        return response()->json(['sku' => $sku, 'images_added_to_table' => count($remote)]);
    }

    public function forceMapAll(string $sku, Request $request)
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
                'color_id' => $img['color_id'] ?? null,
                'thumbnail_url' => $img['thumb'] ?? null,
                'medium_url' => $img['medium'] ?? null,
                'large_url' => $img['large'] ?? null,
            ]);
        }

        return response()->json(['sku' => $sku, 'images_added_to_table' => count($remote)]);
    }

    public function apiData(string $sku, Request $request)
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

    public function apiDataFull(string $sku, Request $request)
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
