<?php

namespace App\Filament\Resources\Products\StandardProducts\Pages;

use App\Filament\Resources\Products\StandardProducts\StandardProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStandardProduct extends EditRecord
{
    protected static string $resource = StandardProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
