<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\StandardProducts\Pages;

use App\Filament\Resources\Products\StandardProducts\StandardProductResource;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateStandardProduct extends CreateRecord
{
    protected static string $resource = StandardProductResource::class;

    #[Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = Product::TYPE_STANDARD;

        return $data;
    }
}
