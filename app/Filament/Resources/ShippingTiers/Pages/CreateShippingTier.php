<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShippingTiers\Pages;

use App\Filament\Resources\ShippingTiers\ShippingTierResource;
use Filament\Resources\Pages\CreateRecord;

class CreateShippingTier extends CreateRecord
{
    protected static string $resource = ShippingTierResource::class;
}
