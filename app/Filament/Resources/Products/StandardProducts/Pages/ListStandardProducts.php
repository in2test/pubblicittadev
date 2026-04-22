<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\StandardProducts\Pages;

use App\Filament\Resources\Products\StandardProducts\StandardProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListStandardProducts extends ListRecords
{
    protected static string $resource = StandardProductResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
