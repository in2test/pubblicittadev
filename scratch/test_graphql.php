<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->handle(Request::capture());

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

$endpoint = config('services.nwg.endpoint');
$token = config('services.nwg.token');

$query = <<<'GRAPHQL'
query Query($productNumber: String!, $language:String!) {
  productById(productNumber: $productNumber, language: $language) {
    variations {
      skus {
        skuSize {
          webtext
          size
        }
      }
    }
  }
}
GRAPHQL;

$response = Http::withoutVerifying()
    ->withToken($token)
    ->post($endpoint, [
        'query' => $query,
        'variables' => [
            'productNumber' => '029030',
            'language' => 'it',
        ],
    ]);

echo json_encode($response->json(), JSON_PRETTY_PRINT)."\n";
