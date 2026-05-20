<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrintPlacements\Pages;

use App\Filament\Resources\PrintPlacements\PrintPlacementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListPrintPlacements extends ListRecords
{
    protected static string $resource = PrintPlacementResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
