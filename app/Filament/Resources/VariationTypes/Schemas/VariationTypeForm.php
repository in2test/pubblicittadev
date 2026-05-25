<?php

declare(strict_types=1);

namespace App\Filament\Resources\VariationTypes\Schemas;

use App\Enums\ModifierType;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                    Grid::make(3)->schema([
                        TextInput::make('name')
                            ->label('Nome (es. Colore, Taglia, Posizione Stampa)')
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
                        Toggle::make('allow_multiple')
                            ->label('Consenti Selezione Multipla')
                            ->helperText('Permette di scegliere più di un\'opzione per questa variante (es. stampa sia sul fronte che sul retro).')
                            ->default(false),
                    ]),
                ]),

                Section::make('Opzioni')->schema([
                    Repeater::make('options')
                        ->relationship()
                        ->schema([
                            Grid::make(4)->schema([
                                TextInput::make('name')
                                    ->label('Nome Opzione (es. Rosso, XL, Fronte)')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                TextInput::make('value')
                                    ->label('Valore (opzionale)')
                                    ->maxLength(255)
                                    ->columnSpan(1)
                                    ->visible(fn (Get $get) => $get('../../presentation_type') !== 'color_swatch'),

                                ColorPicker::make('color_hex')
                                    ->label('Colore (HEX)')
                                    ->columnSpan(1)
                                    ->visible(fn (Get $get) => $get('../../presentation_type') === 'color_swatch')
                                    ->required(fn (Get $get) => $get('../../presentation_type') === 'color_swatch'),

                                TextInput::make('sort_order')
                                    ->label('Ordinamento')
                                    ->numeric()
                                    ->default(0)
                                    ->columnSpan(1),
                            ]),

                            Grid::make(2)->schema([
                                Select::make('default_modifier_type')
                                    ->label('Tipo Sovrapprezzo di Default')
                                    ->options(ModifierType::class)
                                    ->default(ModifierType::Flat->value)
                                    ->required(),

                                TextInput::make('default_price_modifier')
                                    ->label('Valore Sovrapprezzo di Default')
                                    ->numeric()
                                    ->default(0.00)
                                    ->step(0.01)
                                    ->required(),
                            ]),
                        ])
                        ->orderColumn('sort_order')
                        ->defaultItems(1)
                        ->addActionLabel('Aggiungi Opzione'),
                ]),
            ]);
    }
}
