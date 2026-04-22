<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\StandardProducts\Pages;

use App\Filament\Resources\Products\StandardProducts\StandardProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditStandardProduct extends EditRecord
{
    protected static string $resource = StandardProductResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
