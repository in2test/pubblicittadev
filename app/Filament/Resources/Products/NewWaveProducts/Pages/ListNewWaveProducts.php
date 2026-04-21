<?php

namespace App\Filament\Resources\Products\NewWaveProducts\Pages;

use App\Filament\Resources\Products\NewWaveProducts\NewWaveProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNewWaveProducts extends ListRecords
{
    protected static string $resource = NewWaveProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
