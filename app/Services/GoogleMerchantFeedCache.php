<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Stores the rendered Google Merchant feed so that crawlers never wait for it.
 *
 * Google fetches the feed on its own schedule, often more often than the
 * catalogue changes. Rendering it on every request costs seconds of CPU for a
 * result that is identical most of the time, so the rendered XML is kept in
 * the cache.
 *
 * The cache is refreshed whenever the catalogue changes: the rebuild is
 * deferred, so it runs once the request that touched the catalogue is over
 * and never delays the response.
 */
class GoogleMerchantFeedCache
{
    private const string CACHE_KEY = 'feed.google-merchant.xml';

    /**
     * Guards against a burst of changes scheduling a burst of rebuilds.
     */
    private bool $refreshScheduled = false;

    public function __construct(
        private readonly GoogleMerchantFeedBuilder $builder,
    ) {}

    /**
     * The cached feed, rendering it when the cache is empty.
     */
    public function get(): string
    {
        $cached = $this->store()->get(self::CACHE_KEY);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        return $this->refresh();
    }

    /**
     * Whether a feed is already cached.
     */
    public function isFresh(): bool
    {
        $cached = $this->store()->get(self::CACHE_KEY);

        return is_string($cached) && $cached !== '';
    }

    /**
     * Render the feed and store it in the cache.
     */
    public function refresh(): string
    {
        $xml = $this->builder->build();

        $this->store()->put(self::CACHE_KEY, $xml, $this->ttl());

        return $xml;
    }

    /**
     * Drop the cached feed, forcing the next request to render it again.
     */
    public function forget(): void
    {
        $this->store()->forget(self::CACHE_KEY);
    }

    /**
     * Schedule a rebuild of the feed once the current request is over.
     *
     * Rebuilding takes a few seconds, which is too long to keep the visitor
     * waiting, so it is deferred to the end of the request. The callback is
     * named, so a burst of changes results in a single rebuild, and it is
     * marked as "always" so it also runs when the request or the job failed.
     *
     * The stale feed is dropped straight away, so a crawler arriving before
     * the rebuild finishes renders the feed itself instead of being served
     * outdated data.
     */
    public function scheduleRefresh(): void
    {
        $this->forget();

        if ($this->refreshScheduled) {
            return;
        }

        $this->refreshScheduled = true;

        defer(function (): void {
            $this->refreshScheduled = false;

            try {
                $this->refresh();
            } catch (Throwable $exception) {
                Log::error('Failed to rebuild the Google Merchant feed: '.$exception->getMessage());
            }
        }, 'google-merchant-feed-refresh', always: true);
    }

    /**
     * How long a rendered feed is considered fresh.
     */
    private function ttl(): int
    {
        return (int) config('services.google.feed_cache_ttl', 21600);
    }

    private function store(): Repository
    {
        return Cache::store((string) config('services.google.feed_cache_store', 'file'));
    }
}
