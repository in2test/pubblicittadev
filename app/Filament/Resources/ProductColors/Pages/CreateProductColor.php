<?php

namespace App\Filament\Resources\ProductColors\Pages;

use App\Filament\Resources\ProductColors\ProductColorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductColor extends CreateRecord
{
    protected static string $resource = ProductColorResource::class;
}
