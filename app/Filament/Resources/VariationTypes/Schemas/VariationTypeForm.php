<?php

declare(strict_types=1);

namespace App\Filament\Resources\VariationTypes\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class VariationTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dettagli Tipo di Variante')->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label('Nome (es. Colore, Taglia)')
                            ->required()
                            ->maxLength(255),
                        Select::make('presentation_type')
                            ->label('Tipo di Visualizzazione')
                            ->options([
                                'select' => 'Menu a tendina (Select)',
                                'radio' => 'Bottoni',
                                'color_swatch' => 'Campioni di Colore (Color Swatch)',
                            ])
                            ->required()
                            ->default('select')
                            ->live(),
                    ]),
                ]),

                Section::make('Opzioni')->schema([
                    Repeater::make('options')
                        ->relationship()
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('name')
                                    ->label('Nome (es. Rosso, XL)')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                TextInput::make('value')
                                    ->label('Valore / Codice HEX')
                                    ->maxLength(255)
                                    ->columnSpan(1)
                                    ->visible(fn (Get $get) => $get('../../presentation_type') !== 'color_swatch'),

                                ColorPicker::make('value')
                                    ->label('Colore')
                                    ->columnSpan(1)
                                    ->visible(fn (Get $get) => $get('../../presentation_type') === 'color_swatch'),

                                TextInput::make('sort_order')
                                    ->label('Ordinamento')
                                    ->numeric()
                                    ->default(0)
                                    ->columnSpan(1),
                            ]),
                        ])
                        ->orderColumn('sort_order')
                        ->defaultItems(1)
                        ->addActionLabel('Aggiungi Opzione'),
                ]),
            ]);
    }
}
