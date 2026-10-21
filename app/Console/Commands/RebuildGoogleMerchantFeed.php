<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\GoogleMerchantFeedCache;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Rebuild the Google Merchant feed and store it in the cache')]
#[Signature('feed:google-merchant {--force : Rebuild even when a fresh feed is already cached}')]
class RebuildGoogleMerchantFeed extends Command
{
    /**
     * Execute the console command.
     *
     * The feed is rebuilt automatically whenever the catalogue changes, so this
     * command is only needed to warm the cache on demand.
     */
    public function handle(GoogleMerchantFeedCache $feedCache): int
    {
        if (! $this->option('force') && $feedCache->isFresh()) {
            $this->info('A fresh feed is already cached, nothing to do. Use --force to rebuild it.');

            return 0;
        }

        $this->info('Building the Google Merchant feed...');

        $startedAt = now();
        $xml = $feedCache->refresh();

        $this->info(sprintf(
            'Feed cached in %.2fs (%s bytes).',
            $startedAt->diffInMilliseconds(now()) / 1000,
            number_format(strlen($xml)),
        ));

        return 0;
    }
}
