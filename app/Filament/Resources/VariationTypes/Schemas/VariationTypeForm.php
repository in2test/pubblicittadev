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

                                // The presentation type drives conditional visibility in the options repeater below.
                                Select::make('presentation_type')
                                    ->label('Tipo di Visualizzazione')
                                    ->options([
                                        'select' => 'Menu a tendina (Select)',
                                        'radio' => 'Matrice Quantità / Input',
                                        'color_swatch' => 'Campioni di Colore (Color Swatch)',
                                    ])
                                    ->required()
                                    ->default('select')
                                    ->live(), // Crucial for making the UI reactive when this selection changes

                                // Allows users to configure if this variation type supports selecting multiple options simultaneously.
                                Toggle::make('allow_multiple')
                                    ->label('Consenti Selezione Multipla')
                                    ->helperText('Permette di scegliere più di un\'opzione per questa variante (es. stampa sia sul fronte che sul retro).')
                                    ->default(false),

                                Toggle::make('expose_in_url')
                                    ->label('Esponi nell\'URL')
                                    ->helperText('Mostra questa variante nell\'URL del prodotto (es. ?colore=123) per facilitare la condivisione di configurazioni specifiche.')
                                    ->default(false),
                            ]),

                        Tab::make('Opzioni della Variante')
                            ->icon('heroicon-m-list-bullet')
                            ->schema([
                                // A Repeater component is used here to manage a 'HasMany' relationship with the options.
                                // It allows adding, editing, reordering, and deleting options directly within this form.
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

                                            TextInput::make('description')
                                                ->label('Descrizione (Opzionale)')
                                                ->placeholder('es. Fino a 10x10 cm')
                                                ->maxLength(255)
                                                ->columnSpan([
                                                    'default' => 12,
                                                    'md' => 4,
                                                ]),

                                            // The text 'value' field is dynamically hidden if the overall presentation type is 'color_swatch'.
                                            // The Get utility reaches up two levels (../../) to read the parent presentation_type value.
                                            TextInput::make('value')
                                                ->label('Valore')
                                                ->placeholder('opzionale')
                                                ->maxLength(255)
                                                ->visible(fn (Get $get) => $get('../../presentation_type') !== 'color_swatch')
                                                ->columnSpan([
                                                    'default' => 12,
                                                    'md' => 4,
                                                ]),

                                            // The 'color_hex' field is only visible and required when the presentation type is 'color_swatch'.
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
                                            // Modifier configuration: Determines how this option affects the overall pricing.
                                            // It leverages the ModifierType enum for available flat or percentage modifiers.
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
                                    ->orderColumn('sort_order') // Sorts options using the dedicated 'sort_order' column
                                    ->defaultItems(1)
                                    ->addActionLabel('Aggiungi Opzione')
                                    ->reorderable(true)
                                    ->cloneable() // Provides a quick way to duplicate an existing option
                                    ->collapsible() // Keeps the UI clean by allowing options to be collapsed
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null), // Dynamically shows the option name as the repeater item label
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
