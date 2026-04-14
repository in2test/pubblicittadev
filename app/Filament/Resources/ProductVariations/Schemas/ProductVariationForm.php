<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductVariations\Schemas;

use App\Models\Color;
use App\Models\PrintPlacement;
use App\Models\PrintSide;
use App\Models\Size;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductVariationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Prodotto')
                    ->relationship('product', 'name')
                    ->required(),
                Select::make('color_id')
                    ->label('Colore')
                    ->options(Color::query()->pluck('color_name', 'id'))
                    ->nullable(),
                Select::make('size_id')
                    ->label('Taglia')
                    ->options(Size::query()->pluck('size', 'id'))
                    ->nullable(),
                Select::make('print_placement_id')
                    ->label('Posizione Stampa')
                    ->options(PrintPlacement::query()->pluck('name', 'id'))
                    ->nullable(),
                Select::make('print_side_id')
                    ->label('Lato Stampa')
                    ->options(PrintSide::query()->pluck('name', 'id'))
                    ->nullable(),
                TextInput::make('sku')
                    ->label('SKU')
                    ->unique(ignoreRecord: true),
                TextInput::make('quantity')
                    ->label('Giacenza in Stock')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_available')
                    ->label('Disponibile')
                    ->default(true),
            ]);
    }
}
