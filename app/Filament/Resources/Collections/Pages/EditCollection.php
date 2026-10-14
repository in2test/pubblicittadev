<?php

declare(strict_types=1);

namespace App\Filament\Resources\Collections\Pages;

use App\Filament\Resources\Collections\CollectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditCollection extends EditRecord
{
    protected static string $resource = CollectionResource::class;

    #[Override]
    public function getTitle(): string
    {
        return 'Modifica Collezione';
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
