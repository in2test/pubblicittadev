<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GoogleMerchantFeedBuilder;
use Illuminate\Http\Response;
use Throwable;

class GoogleMerchantFeedController extends Controller
{
    public function index(GoogleMerchantFeedBuilder $feedBuilder): Response
    {
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        try {
            return response($feedBuilder->build(), 200)
                ->header('Content-Type', 'text/xml');
        } catch (Throwable $e) {
            return response($e->getMessage()."\n".$e->getTraceAsString(), 500)
                ->header('Content-Type', 'text/plain');
        }
    }
}
