<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ProductResource;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;
}
