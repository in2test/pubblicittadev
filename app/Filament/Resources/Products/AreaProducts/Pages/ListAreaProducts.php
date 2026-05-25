<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\AreaProducts\Pages;

use App\Filament\Resources\Products\AreaProducts\AreaProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListAreaProducts extends ListRecords
{
    protected static string $resource = AreaProductResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
