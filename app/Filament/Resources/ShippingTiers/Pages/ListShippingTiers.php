<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShippingTiers\Pages;

use App\Filament\Resources\ShippingTiers\ShippingTierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListShippingTiers extends ListRecords
{
    protected static string $resource = ShippingTierResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
