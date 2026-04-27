<?php

declare(strict_types=1);

namespace App\Filament\Resources\CategoryQuantityDiscounts\Pages;

use App\Filament\Resources\CategoryQuantityDiscounts\CategoryQuantityDiscountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListCategoryQuantityDiscounts extends ListRecords
{
    #[Override]
    public function getTitle(): string
    {
        return 'Category Quantity Discounts';
    }

    protected static string $resource = CategoryQuantityDiscountResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
