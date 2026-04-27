<?php

declare(strict_types=1);

namespace App\Filament\Resources\CategoryQuantityDiscounts\Schemas;

use Filament\Forms\Components\NumberInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryQuantityDiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        // Runtime fallback in case NumberInput is not available in the installed Filament version
        $minQuantityField = class_exists("\Filament\\Forms\\Components\\NumberInput")
            ? NumberInput::make('min_quantity')->label('Quantità Minima')->min(1)->required()
            : TextInput::make('min_quantity')->label('Quantità Minima')->required();

        $maxQuantityField = class_exists("\Filament\\Forms\\Components\\NumberInput")
            ? NumberInput::make('max_quantity')->label('Quantità Massima')->min(1)->nullable()
            : TextInput::make('max_quantity')->label('Quantità Massima')->nullable();

        $discountValueField = class_exists("\Filament\\Forms\\Components\\NumberInput")
            ? NumberInput::make('discount_value')->label('Valore sconto')->step(0.01)->required()
            : TextInput::make('discount_value')->label('Valore sconto')->required();

        return $schema->components([
            Select::make('category_id')
                ->label('Categoria')
                ->relationship('category', 'name')
                ->required(),
            $minQuantityField,
            $maxQuantityField,
            Select::make('discount_type')
                ->label('Tipo di sconto')
                ->options([
                    'percent' => 'Percent',
                    'fixed' => 'Fisso',
                ])
                ->required(),
            $discountValueField,
            Textarea::make('description')
                ->label('Descrizione')
                ->nullable(),
        ]);
    }
}
