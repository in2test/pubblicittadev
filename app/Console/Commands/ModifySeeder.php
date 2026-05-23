<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:modify-seeder')]
#[Description('Command description')]
class ModifySeeder extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        //
    }
}
