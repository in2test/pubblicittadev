<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateProduct extends CreateRecord
{
    #[Override]
    public function getTitle(): string
    {
        return 'Nuovo Prodotto';
    }

    protected static string $resource = ProductResource::class;
}
