<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\ItemProducts\Pages;

use App\Filament\Resources\Products\ItemProducts\ItemProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListItemProducts extends ListRecords
{
    protected static string $resource = ItemProductResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
