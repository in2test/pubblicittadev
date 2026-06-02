<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShippingTiers\Pages;

use App\Filament\Resources\ShippingTiers\ShippingTierResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditShippingTier extends EditRecord
{
    protected static string $resource = ShippingTierResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
