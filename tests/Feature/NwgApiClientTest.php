<?php

use App\Services\NwgApiClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('sends a language-specific GraphQL query and returns the product data', function () {
    config([
        'services.nwg.endpoint' => 'https://nwg.test/graphql',
        'services.nwg.token' => 'test-token',
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://nwg.test/graphql' => Http::response([
            'data' => [
                'productById' => [
                    'productName' => 'Test product',
                ],
            ],
        ]),
    ]);

    $result = app(NwgApiClient::class)->fetchFullGraphQLProductData('SKU-123', 'fr');

    expect($result)->toBe(['productName' => 'Test product']);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://nwg.test/graphql'
        && $request->hasHeader('Authorization', 'Bearer test-token')
        && $request['variables'] === ['productNumber' => 'SKU-123', 'language' => 'fr']
        && str_contains($request['query'], 'productName'));
});

it('returns null when the NewWave API responds unsuccessfully', function () {
    config([
        'services.nwg.endpoint' => 'https://nwg.test/graphql',
        'services.nwg.token' => 'test-token',
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://nwg.test/graphql' => Http::response('Unavailable', 503),
    ]);

    $result = app(NwgApiClient::class)->getBasicProductData('SKU-123');

    expect($result)->toBeNull();
});
