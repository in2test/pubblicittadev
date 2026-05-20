<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrintPlacements\Pages;

use App\Filament\Resources\PrintPlacements\PrintPlacementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditPrintPlacement extends EditRecord
{
    protected static string $resource = PrintPlacementResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
