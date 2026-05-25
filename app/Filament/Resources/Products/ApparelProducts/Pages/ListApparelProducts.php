<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\ApparelProducts\Pages;

use App\Filament\Resources\Products\ApparelProducts\ApparelProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListApparelProducts extends ListRecords
{
    protected static string $resource = ApparelProductResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
