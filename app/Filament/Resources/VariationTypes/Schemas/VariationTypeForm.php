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
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class VariationTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Dettagli')
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nome')
                                    ->helperText('es. Colore, Taglia, Posizione Stampa')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('presentation_type')
                                    ->label('Tipo di Visualizzazione')
                                    ->options([
                                        'select' => 'Menu a tendina (Select)',
                                        'radio' => 'Matrice Quantità / Input',
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

                        Tab::make('Opzioni della Variante')
                            ->icon('heroicon-m-list-bullet')
                            ->schema([
                                Repeater::make('options')
                                    ->relationship()
                                    ->schema([
                                        Grid::make(12)->schema([
                                            TextInput::make('name')
                                                ->label('Nome Opzione')
                                                ->placeholder('es. Rosso, XL, Fronte')
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpan([
                                                    'default' => 12,
                                                    'md' => 4,
                                                ]),

                                            TextInput::make('value')
                                                ->label('Valore')
                                                ->placeholder('opzionale')
                                                ->maxLength(255)
                                                ->visible(fn (Get $get) => $get('../../presentation_type') !== 'color_swatch')
                                                ->columnSpan([
                                                    'default' => 12,
                                                    'md' => 4,
                                                ]),

                                            ColorPicker::make('color_hex')
                                                ->label('Colore (HEX)')
                                                ->visible(fn (Get $get) => $get('../../presentation_type') === 'color_swatch')
                                                ->required(fn (Get $get) => $get('../../presentation_type') === 'color_swatch')
                                                ->columnSpan([
                                                    'default' => 12,
                                                    'md' => 4,
                                                ]),

                                            TextInput::make('sort_order')
                                                ->label('Ordine')
                                                ->numeric()
                                                ->default(0)
                                                ->columnSpan([
                                                    'default' => 12,
                                                    'md' => 4,
                                                ]),
                                        ]),

                                        Grid::make(12)->schema([
                                            Select::make('default_modifier_type')
                                                ->label('Tipo Sovrapprezzo di Default')
                                                ->options(ModifierType::class)
                                                ->default(ModifierType::Flat->value)
                                                ->required()
                                                ->columnSpan([
                                                    'default' => 12,
                                                    'md' => 6,
                                                ]),

                                            TextInput::make('default_price_modifier')
                                                ->label('Valore Sovrapprezzo di Default')
                                                ->numeric()
                                                ->default(0.00)
                                                ->step(0.01)
                                                ->required()
                                                ->columnSpan([
                                                    'default' => 12,
                                                    'md' => 6,
                                                ]),
                                        ]),
                                    ])
                                    ->orderColumn('sort_order')
                                    ->defaultItems(1)
                                    ->addActionLabel('Aggiungi Opzione')
                                    ->reorderable(true)
                                    ->cloneable()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
