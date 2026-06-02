<?php

declare(strict_types=1);

namespace App\Filament\Resources\PortfolioItems\Pages;

use App\Filament\Resources\PortfolioItems\PortfolioItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditPortfolioItem extends EditRecord
{
    protected static string $resource = PortfolioItemResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
