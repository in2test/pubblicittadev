<?php

namespace App\Filament\Resources\ProductColors\Pages;

use App\Filament\Resources\ProductColors\ProductColorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductColors extends ListRecords
{
    protected static string $resource = ProductColorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
