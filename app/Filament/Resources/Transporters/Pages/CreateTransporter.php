<?php

declare(strict_types=1);

namespace App\Filament\Resources\Transporters\Pages;

use App\Filament\Resources\Transporters\TransporterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTransporter extends CreateRecord
{
    protected static string $resource = TransporterResource::class;
}
