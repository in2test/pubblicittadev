<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\AreaProducts\Pages;

use App\Filament\Resources\Products\AreaProducts\AreaProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditAreaProduct extends EditRecord
{
    protected static string $resource = AreaProductResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
