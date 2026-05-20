<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrintSides\Pages;

use App\Filament\Resources\PrintSides\PrintSideResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListPrintSides extends ListRecords
{
    protected static string $resource = PrintSideResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
