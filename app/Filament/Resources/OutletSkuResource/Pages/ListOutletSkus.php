<?php

declare(strict_types=1);

namespace App\Filament\Resources\OutletSkuResource\Pages;

use App\Filament\Resources\OutletSkuResource;
use Filament\Resources\Pages\ListRecords;

class ListOutletSkus extends ListRecords
{
    protected static string $resource = OutletSkuResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
