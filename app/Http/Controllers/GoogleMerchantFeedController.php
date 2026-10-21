<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GoogleMerchantFeedCache;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class GoogleMerchantFeedController extends Controller
{
    /**
     * The feed is served gzipped when the crawler accepts it: the XML shrinks
     * from roughly 13 MB to a few hundred kilobytes.
     */
    private const int GZIP_LEVEL = 6;

    public function index(Request $request, GoogleMerchantFeedCache $feedCache): Response
    {
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        try {
            $xml = $feedCache->get();

            return $this->respond($request, $xml);
        } catch (Throwable $e) {
            return response($e->getMessage()."\n".$e->getTraceAsString(), 500)
                ->header('Content-Type', 'text/plain');
        }
    }

    private function respond(Request $request, string $xml): Response
    {
        if ($this->acceptsGzip($request)) {
            $compressed = gzencode($xml, self::GZIP_LEVEL);

            if ($compressed !== false) {
                return response($compressed, 200)
                    ->header('Content-Type', 'text/xml')
                    ->header('Content-Encoding', 'gzip')
                    ->header('Content-Length', (string) strlen($compressed));
            }
        }

        return response($xml, 200)
            ->header('Content-Type', 'text/xml');
    }

    private function acceptsGzip(Request $request): bool
    {
        $acceptEncoding = (string) $request->header('Accept-Encoding', '');

        return str_contains(strtolower($acceptEncoding), 'gzip');
    }
}
