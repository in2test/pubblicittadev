<?php

declare(strict_types=1);

namespace App\Filament\Resources\CategoryQuantityDiscounts\Pages;

use App\Filament\Resources\CategoryQuantityDiscounts\CategoryQuantityDiscountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategoryQuantityDiscount extends CreateRecord
{
    protected static string $resource = CategoryQuantityDiscountResource::class;
}
