<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrintSides\Pages;

use App\Filament\Resources\PrintSides\PrintSideResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Override;

class ListPrintSides extends ListRecords
{
    protected static string $resource = PrintSideResource::class;

    #[Override]
    public function table(Table $table): Table
    {
        return PrintSideResource::table($table);
    }
}
