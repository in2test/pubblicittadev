<?php

namespace App\Filament\Resources\Products\NewWaveProducts\Pages;

use App\Filament\Resources\Products\NewWaveProducts\NewWaveProductResource;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;

class CreateNewWaveProduct extends CreateRecord
{
    protected static string $resource = NewWaveProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = Product::TYPE_NEWWAVE;

        return $data;
    }
}
