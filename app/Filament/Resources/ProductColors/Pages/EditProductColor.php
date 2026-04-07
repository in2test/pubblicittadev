<?php

namespace App\Filament\Resources\ProductColors\Pages;

use App\Filament\Resources\ProductColors\ProductColorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductColor extends EditRecord
{
    protected static string $resource = ProductColorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
