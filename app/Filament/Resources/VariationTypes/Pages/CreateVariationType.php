<?php

declare(strict_types=1);

namespace App\Filament\Resources\VariationTypes\Pages;

use App\Filament\Resources\VariationTypes\VariationTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVariationType extends CreateRecord
{
    protected static string $resource = VariationTypeResource::class;
}
