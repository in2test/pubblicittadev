<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrintSides\Pages;

use App\Filament\Resources\PrintSides\PrintSideResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePrintSide extends CreateRecord
{
    protected static string $resource = PrintSideResource::class;
}
