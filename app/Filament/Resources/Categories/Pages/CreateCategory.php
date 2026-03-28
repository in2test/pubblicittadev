<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateCategory extends CreateRecord
{
    #[Override]
    public function getTitle(): string
    {
        return 'Nuova Categoria';
    }

    protected static string $resource = CategoryResource::class;
}
