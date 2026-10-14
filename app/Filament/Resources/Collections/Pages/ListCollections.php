<?php

declare(strict_types=1);

namespace App\Filament\Resources\Collections\Pages;

use App\Filament\Resources\Collections\CollectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListCollections extends ListRecords
{
    protected static string $resource = CollectionResource::class;

    #[Override]
    public function getTitle(): string
    {
        return 'Collezioni & Bundle';
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuova Collezione'),
        ];
    }
}
