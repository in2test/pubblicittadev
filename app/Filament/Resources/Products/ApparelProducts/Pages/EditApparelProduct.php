<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\ApparelProducts\Pages;

use App\Filament\Resources\Products\ApparelProducts\ApparelProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditApparelProduct extends EditRecord
{
    protected static string $resource = ApparelProductResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
