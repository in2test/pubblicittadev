<?php

namespace App\Filament\Resources\Products\StandardProducts\Pages;

use App\Filament\Resources\Products\StandardProducts\StandardProductResource;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;

class CreateStandardProduct extends CreateRecord
{
    protected static string $resource = StandardProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = Product::TYPE_STANDARD;

        return $data;
    }
}
