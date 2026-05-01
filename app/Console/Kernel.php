<?php

declare(strict_types=1);

namespace App\Console;

use App\Console\Commands\SyncProductImagesCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Override;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        SyncProductImagesCommand::class,
        \App\Console\Commands\ConvertProductImagesCommand::class,
        \App\Console\Commands\RefreshRemoteImagesCommand::class,
        \App\Console\Commands\SyncAllRemoteImagesCommand::class,
        \App\Console\Commands\DiagnoseNwgSku::class,
    ];

    #[Override]
    protected function schedule(Schedule $schedule): void
    {
        // No automatic scheduling in Phase 1. Use manual dispatch.
    }

    #[Override]
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
