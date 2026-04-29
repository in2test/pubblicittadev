<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugController extends Controller
{
    public function remoteImages(string $sku, Request $request)
    {
        $product = Product::where('sku', $sku)->first();
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }
        return response()->json([
            'sku' => $sku,
            'remote_images' => $product->remote_images,
            'all_images' => $product->getAllImages(),
        ]);
    }

    public function remoteImagesFromPayload(string $sku, Request $request)
    {
        $language = $request->query('language', 'it');
        /** @var ProductAvailabilityService $service */
        $service = app(ProductAvailabilityService::class);
        $payload = $service->fetchFullGraphQLProductData($sku, $language);
        if (!$payload) {
            return response()->json(['error' => 'Full payload not available'], 404);
        }
        $remote = method_exists($service, 'mapFullProductPayloadToRemoteImages') ? $service->mapFullProductPayloadToRemoteImages($payload) : [];
        return response()->json(['sku'=>$sku, 'remote_images'=>$remote]);
    }

    public function mapPayloadToRemoteImages(string $sku, Request $request)
    {
        $language = $request->query('language', 'it');
        /** @var ProductAvailabilityService $service */
        $service = app(ProductAvailabilityService::class);
        $payload = method_exists($service, 'fetchFullGraphQLProductData') ? $service->fetchFullGraphQLProductData($sku, $language) : null;
        if (!$payload) {
            return response()->json(['error' => 'Full payload not available'], 404);
        }
        $remote = method_exists($service, 'mapFullProductPayloadToRemoteImages') ? $service->mapFullProductPayloadToRemoteImages($payload) : [];
        $product = Product::where('sku', $sku)->first();
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }
        $product->update(['remote_images' => $remote]);
        return response()->json(['sku'=>$sku, 'remote_images_count'=>count($remote), 'remote_images'=>$remote]);
    }

    public function forceMapAll(string $sku, Request $request)
    {
        $language = $request->query('language', 'it');
        /** @var ProductAvailabilityService $service */
        $service = app(ProductAvailabilityService::class);
        $payload = $service->fetchFullGraphQLProductData($sku, $language) ?? $service->fetchRawProductDataFull($sku, $language);
        if (!$payload) {
            return response()->json(['error' => 'Full payload not available'], 404);
        }
        $remote = $service->mapFullProductPayloadToRemoteImages($payload);
        $product = Product::where('sku', $sku)->first();
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }
        $product->update(['remote_images' => $remote]);
        return response()->json(['sku'=>$sku, 'remote_images_count'=>count($remote), 'remote_images'=>$remote]);
    }

    public function apiData(string $sku, Request $request)
    {
        $language = $request->query('language', 'it');
        /** @var ProductAvailabilityService $service */
        $service = app(ProductAvailabilityService::class);
        $data = method_exists($service, 'fetchRawProductData') ? $service->fetchRawProductData($sku, $language) : null;
        if (! $data && method_exists($service, 'fetchRawProductDataFull')) {
            $data = $service->fetchRawProductDataFull($sku, $language);
        }
        if (! $data && method_exists($service, 'fetchFullGraphQLProductData')) {
            $data = $service->fetchFullGraphQLProductData($sku, $language);
        }
        if (! $data) {
            return response()->json(['error' => 'Data not found or API error'], 404);
        }
        return response()->json(['sku'=>$sku, 'language'=>$language, 'data'=>$data]);
    }

    public function apiDataFull(string $sku, Request $request)
    {
        $language = $request->query('language', 'it');
        /** @var ProductAvailabilityService $service */
        $service = app(ProductAvailabilityService::class);
        $data = method_exists($service, 'fetchFullGraphQLProductData') ? $service->fetchFullGraphQLProductData($sku, $language) : null;
        if (!$data) {
            $data = $service->fetchRawProductDataFull($sku, $language);
        }
        if (! $data) {
            return response()->json(['error' => 'Data not found or API error'], 404);
        }
        return response()->json(['sku'=>$sku, 'language'=>$language, 'data'=>$data]);
    }
}
