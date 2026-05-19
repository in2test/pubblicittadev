<?php

namespace App\Filament\Resources\PrintPlacements\Pages;

use App\Filament\Resources\PrintPlacements\PrintPlacementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPrintPlacement extends EditRecord
{
    protected static string $resource = PrintPlacementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
