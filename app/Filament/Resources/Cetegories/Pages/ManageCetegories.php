<?php

namespace App\Filament\Resources\Cetegories\Pages;

use App\Filament\Resources\Cetegories\CetegoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCetegories extends ManageRecords
{
    protected static string $resource = CetegoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
