<?php

declare(strict_types=1);

namespace App\Filament\Resources\VariationTypes\Pages;

use App\Filament\Resources\VariationTypes\VariationTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditVariationType extends EditRecord
{
    protected static string $resource = VariationTypeResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
