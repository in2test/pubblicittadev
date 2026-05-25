<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\ItemProducts\Pages;

use App\Filament\Resources\Products\ItemProducts\ItemProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditItemProduct extends EditRecord
{
    protected static string $resource = ItemProductResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
