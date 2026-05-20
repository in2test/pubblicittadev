<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrintSides\Pages;

use App\Filament\Resources\PrintSides\PrintSideResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditPrintSide extends EditRecord
{
    protected static string $resource = PrintSideResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
