<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;

final class ProductBrandField
{
    /**
     * Brand selector shared by the standard and the NewWave product forms.
     */
    public static function getBrandField(?string $helperText = null): Select
    {
        return Select::make('brand_id')
            ->label('Brand')
            ->relationship('brand_relation', 'name')
            ->searchable()
            ->preload()
            ->nullable()
            ->helperText($helperText ?? 'Lascia vuoto per usare il brand predefinito del prodotto.');
    }
}
