<?php

declare(strict_types=1);

namespace App\Filament\Resources\Transporters\Pages;

use App\Filament\Resources\Transporters\TransporterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListTransporters extends ListRecords
{
    protected static string $resource = TransporterResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
