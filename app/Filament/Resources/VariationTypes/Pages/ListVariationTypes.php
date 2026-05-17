<?php

declare(strict_types=1);

namespace App\Filament\Resources\VariationTypes\Pages;

use App\Filament\Resources\VariationTypes\VariationTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListVariationTypes extends ListRecords
{
    protected static string $resource = VariationTypeResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
