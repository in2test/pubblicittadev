<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Color;
use App\Models\PrintPlacement;
use App\Services\ProductAvailabilityService;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class NewWaveProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Prodotto NewWave')
                    ->tabs([
                        Tab::make('Configurazione Generale')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Section::make('Identificazione')
                                            ->columnSpan(1)
                                            ->schema([
                                                TextInput::make('sku')
                                                    ->label('Codice NWG')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Set $set, ?string $state, ?Model $record, ProductAvailabilityService $service) {
                                                        if (! $state) {
                                                            return;
                                                        }
                                                        $info = $service->fetchBasicInfo($state);
                                                        if ($info && ! empty($info['name'])) {
                                                            $set('name', $info['name']);
                                                            $set('price', $info['price']);
                                                            $set('description', $info['description'] ?? '');
                                                        }
                                                    }),
                                                TextInput::make('slug')
                                                    ->required(),
                                                Select::make('category_id')
                                                    ->label('Categoria')
                                                    ->relationship('category', 'name')
                                                    ->required(),
                                            ]),

                                        Section::make('Dati API & Overrides')
                                            ->columnSpan(2)
                                            ->schema([
                                                Grid::make(2)
                                                    ->schema([
                                                        TextInput::make('name')
                                                            ->label('Nome Prodotto')
                                                            ->disabled()
                                                            ->dehydrated(),
                                                        TextInput::make('price')
                                                            ->label('Prezzo Base (€)')
                                                            ->disabled(fn (Get $get) => ! $get('override_price'))
                                                            ->dehydrated()
                                                            ->numeric()
                                                            ->prefix('€'),
                                                        TextInput::make('offer_price')
                                                            ->label('Prezzo Offerta (€)')
                                                            ->numeric()
                                                            ->prefix('€')
                                                            ->helperText('Lascia vuoto se non c\'è offerta. Se impostato, verrà mostrato come prezzo principale.'),
                                                        Toggle::make('override_price')
                                                            ->label('Sovrascrivi Prezzo Base')
                                                            ->live(),
                                                        Toggle::make('override_description')
                                                            ->label('Sovrascrivi Descrizione')
                                                            ->live(),
                                                        Select::make('disabled_colors')
                                                            ->label('Disabilita Colori')
                                                            ->multiple()
                                                            ->options(fn (?Model $record) => $record
                                                                ? Color::whereHas('variations', fn ($q) => $q->where('product_id', $record->id))
                                                                    ->pluck('color_name', 'id')
                                                                    ->all()
                                                                : []
                                                            )
                                                            ->preload(),
                                                    ]),
                                                Textarea::make('description')
                                                    ->label('Descrizione')
                                                    ->disabled(fn (Get $get) => ! $get('override_description'))
                                                    ->dehydrated()
                                                    ->rows(4),
                                            ]),

                                        Section::make('Personalizzazione Stampa')
                                            ->columnSpanFull()
                                            ->schema([
                                                Repeater::make('productPrintPlacements')
                                                    ->relationship('productPrintPlacements')
                                                    ->schema([
                                                        Select::make('print_placement_id')
                                                            ->label('Posizione')
                                                            ->options(PrintPlacement::pluck('name', 'id'))
                                                            ->required(),
                                                        TextInput::make('additional_price')
                                                            ->label('Sovrapprezzo')
                                                            ->numeric()
                                                            ->prefix('+ €'),
                                                    ])
                                                    ->columns(2)
                                                    ->grid(3),

                                                Select::make('printSides')
                                                    ->relationship('printSides', 'name')
                                                    ->label('Lati di Stampa')
                                                    ->multiple()
                                                    ->preload(),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Galleria & Colori')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Section::make('Caricamento / Cache Immagini')
                                    ->schema([
                                        SpatieMediaLibraryFileUpload::make('images')
                                            ->label('')
                                            ->collection('images')
                                            ->multiple()
                                            ->reorderable()
                                            ->image()
                                            ->imageEditor()
                                            ->panelLayout('grid')
                                            ->preserveFilenames()
                                            ->responsiveImages()
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Organizzazione per Colore')
                                    ->description('Associa ogni immagine ai rispettivi colori per permettere il cambio immagine dinamico sul sito.')
                                    ->schema([
                                        Repeater::make('media')
                                            ->label('')
                                            ->relationship('media', fn ($query) => $query->where('collection_name', 'images'))
                                            ->schema([
                                                Grid::make(3)
                                                    ->schema([
                                                        TextEntry::make('preview')
                                                            ->label('Anteprima')
                                                            ->state(fn ($record) => $record ? new HtmlString("<img src='{$record->getUrl('thumbnail')}' class='h-32 w-auto rounded border shadow-sm mx-auto'>") : 'N/A'),
                                                        Grid::make(1)
                                                            ->schema([
                                                                Select::make('custom_properties.color_ids')
                                                                    ->label('Associa a Colori')
                                                                    ->multiple()
                                                                    ->options(fn () => Color::pluck('color_name', 'id'))
                                                                    ->preload()
                                                                    ->searchable(),
                                                                TextInput::make('custom_properties.alt')
                                                                    ->label('Testo Alt')
                                                                    ->placeholder('es. Vista laterale'),
                                                                Checkbox::make('custom_properties.is_manual')
                                                                    ->label('Gestione Manuale (Blocca Sync API)')
                                                                    ->inline(false),
                                                            ])
                                                            ->columnSpan(2),
                                                    ]),
                                            ])
                                            ->grid(2)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
