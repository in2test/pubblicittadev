<?php

namespace App\Filament\Resources\PrintPlacements\Pages;

use App\Filament\Resources\PrintPlacements\PrintPlacementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrintPlacements extends ListRecords
{
    protected static string $resource = PrintPlacementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
