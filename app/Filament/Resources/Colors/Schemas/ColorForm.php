<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductColors\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductColorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('color_name')
                    ->unique()
                    ->required(),
                TextInput::make('color_hex'),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
