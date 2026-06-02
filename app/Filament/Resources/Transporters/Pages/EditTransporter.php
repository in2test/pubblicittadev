<?php

declare(strict_types=1);

namespace App\Filament\Resources\Transporters\Pages;

use App\Filament\Resources\Transporters\TransporterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditTransporter extends EditRecord
{
    protected static string $resource = TransporterResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
