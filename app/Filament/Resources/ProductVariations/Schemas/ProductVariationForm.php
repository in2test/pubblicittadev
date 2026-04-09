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
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->required(),
                Select::make('color_id')
                    ->label('Color')
                    ->options(Color::query()->pluck('color_name', 'id'))
                    ->nullable(),
                Select::make('size_id')
                    ->label('Size')
                    ->options(Size::query()->pluck('size', 'id'))
                    ->nullable(),
                Select::make('print_placement_id')
                    ->label('Print Placement')
                    ->options(PrintPlacement::query()->pluck('name', 'id'))
                    ->nullable(),
                Select::make('print_side_id')
                    ->label('Print Side')
                    ->options(PrintSide::query()->pluck('name', 'id'))
                    ->nullable(),
                TextInput::make('sku')
                    ->label('SKU')
                    ->unique(ignoreRecord: true),
                TextInput::make('quantity')
                    ->label('Stock Quantity')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_available')
                    ->label('Available')
                    ->default(true),
            ]);
    }
}
