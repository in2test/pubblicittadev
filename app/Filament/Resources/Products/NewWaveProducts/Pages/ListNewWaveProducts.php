<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\NewWaveProducts\Pages;

use App\Filament\Resources\Products\NewWaveProducts\NewWaveProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListNewWaveProducts extends ListRecords
{
    protected static string $resource = NewWaveProductResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
