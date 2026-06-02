<?php

declare(strict_types=1);

namespace App\Filament\Resources\PortfolioItems\Pages;

use App\Filament\Resources\PortfolioItems\PortfolioItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListPortfolioItems extends ListRecords
{
    protected static string $resource = PortfolioItemResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
