<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use App\Models\PrintPlacement;
use App\Models\PrintSide;
use App\Models\Product;
use App\Models\VariationOption;
use App\Support\SlugGenerator;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                static::getTypeField(),

                Section::make('Informazioni di Base')
                    ->schema([
                        Grid::make(2)->schema([
                            static::getNameField(),
                            static::getSlugField(),
                            static::getSkuField(),
                            static::getCategoryField(),
                            static::getPersonalizationTypeField(),
                        ]),
                        static::getDescriptionField(),
                    ]),

                Section::make('Prezzi, Varianti e Inventario')
                    ->schema([
                        Grid::make(2)->schema([
                            static::getPriceField(),
                            static::getOfferPriceField(),
                            static::getPricingModelField(),
                            static::getMinAreaField(),
                        ]),
                        static::getVariationTypesField(),
                        static::getSkusRepeater(),
                        static::getPricingTiersRepeater(),
                    ]),

                Section::make('Galleria Immagini')
                    ->schema([
                        static::getImagesField(),
                        static::getColorGallerySection(),
                    ]),

                Section::make('Personalizzazione Stampa')
                    ->description('Definisci le posizioni e i lati di stampa disponibili per questo prodotto.')
                    ->collapsible()
                    ->schema([
                        static::getPrintPlacementsRepeater(),
                        static::getPrintSidesField(),
                    ]),

                Section::make('Stato Prodotto')
                    ->schema([
                        Grid::make(2)->schema([
                            static::getIsActiveField(),
                            static::getIsFeaturedField(),
                        ]),
                    ]),
            ]);
    }

    public static function getTypeField(): Hidden
    {
        return Hidden::make('type')
            ->default(Product::TYPE_STANDARD)
            ->dehydrated();
    }

    public static function getNameField(): TextInput
    {
        return TextInput::make('name')
            ->label('Nome Prodotto')
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, ?string $state, ?Model $record) {
                $slug = SlugGenerator::unique(Product::class, $state, $record);
                $set('slug', $slug);
            })
            ->required();
    }

    public static function getSlugField(): TextInput
    {
        return TextInput::make('slug')
            ->label('Slug')
            ->unique(ignorable: fn ($record) => $record)
            ->required();
    }

    public static function getSkuField(): TextInput
    {
        return TextInput::make('sku')
            ->label('Codice Prodotto Base');
    }

    public static function getCategoryField(): Select
    {
        return Select::make('category_id')
            ->label('Categoria')
            ->relationship('category', 'name')
            ->searchable()
            ->preload()
            ->required()
            ->createOptionForm([
                TextInput::make('name')
                    ->label('Nome Categoria')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, ?Model $record) {
                        $slug = SlugGenerator::unique(Category::class, $state, $record);
                        $set('slug', $slug);
                    })
                    ->required(),
                TextInput::make('slug')
                    ->unique(ignorable: fn ($record) => $record)
                    ->required(),
                Textarea::make('description')
                    ->label('Descrizione'),
                Select::make('parent_id')
                    ->label('Categoria di appartenenza')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
            ])
            ->createOptionAction(function (Action $action) {
                $action->modalHeading('Crea Categoria');
            });
    }

    public static function getDescriptionField(): Textarea
    {
        return Textarea::make('description')
            ->label('Descrizione')
            ->columnSpanFull();
    }

    public static function getPersonalizationTypeField(): Select
    {
        return Select::make('personalization_type')
            ->label('Tipo di Personalizzazione')
            ->options([
                'global' => 'Posizioni di Stampa Standard (es. T-shirt: Fronte, Retro, Manica con sovrapprezzo)',
                'custom' => 'Prezzi a Scaglioni basati sui Lati di Stampa (es. Biglietti da visita)',
            ])
            ->default('global')
            ->dehydrated(false)
            ->live();
    }

    public static function getPriceField(): TextInput
    {
        return TextInput::make('price')
            ->label('Prezzo Base (€)')
            ->required()
            ->numeric()
            ->prefix('€');
    }

    public static function getOfferPriceField(): TextInput
    {
        return TextInput::make('offer_price')
            ->label('Prezzo Offerta (€)')
            ->numeric()
            ->prefix('€');
    }

    public static function getVariationTypesField(): Select
    {
        return Select::make('variationTypes')
            ->label('Tipi di Varianti Disponibili')
            ->relationship('variationTypes', 'name')
            ->multiple()
            ->preload();
    }

    public static function getSkusRepeater(): Repeater
    {
        return Repeater::make('skus')
            ->relationship('skus')
            ->defaultItems(0)
            ->label('Varianti e Inventario (SKU)')
            ->schema([
                TextInput::make('sku')
                    ->label('Codice SKU Variante')
                    ->required(),
                TextInput::make('quantity')
                    ->label('Quantità in Magazzino')
                    ->numeric()
                    ->required()
                    ->default(100),
                Toggle::make('is_available')
                    ->label('Disponibile')
                    ->default(true),
                TextInput::make('override_price')
                    ->label('Prezzo Specifico per Variante (€)')
                    ->numeric()
                    ->prefix('€'),
                Select::make('options')
                    ->label('Opzioni Varianti')
                    ->relationship('options', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ])
            ->columns(2)
            ->grid(2);
    }

    public static function getPricingTiersRepeater(): Repeater
    {
        return Repeater::make('pricingTiers')
            ->relationship('pricingTiers')
            ->defaultItems(0)
            ->label('Prezzi a Scaglioni (Sconti per Quantità)')
            ->schema([
                TextInput::make('min_quantity')
                    ->label('Quantità Minima')
                    ->numeric()
                    ->required()
                    ->default(1),
                TextInput::make('max_quantity')
                    ->label('Quantità Massima (Vuoto per nessun limite)')
                    ->numeric(),
                TextInput::make('price_per_unit')
                    ->label('Prezzo Unitario (€)')
                    ->numeric()
                    ->prefix('€')
                    ->required(),
                Select::make('print_side_id')
                    ->label('Lato di Stampa Associato (Opzionale per Biglietti da Visita)')
                    ->options(fn () => PrintSide::pluck('name', 'id'))
                    ->nullable()
                    ->searchable()
                    ->preload(),
            ])
            ->columns(2)
            ->grid(2)
            ->addActionLabel('Aggiungi Scaglione di Prezzo');
    }

    public static function getImagesField(): SpatieMediaLibraryFileUpload
    {
        return SpatieMediaLibraryFileUpload::make('images')
            ->label('Caricamento Rapido Immagini')
            ->collection('images')
            ->multiple()
            ->reorderable()
            ->imagePreviewHeight('150')
            ->panelLayout('grid')
            ->disk('public')
            ->conversionsDisk('public')
            ->customProperties(fn ($record, $file): array => [
                'alt' => 'descrizione',
            ])
            ->columnSpanFull();
    }

    public static function getColorGallerySection(): Section
    {
        return Section::make('Organizzazione Galleria per Colore')
            ->description('Associa le immagini caricate sopra ai colori disponibili per questo prodotto.')
            ->collapsible()
            ->schema([
                Repeater::make('media')
                    ->relationship('media', fn ($query) => $query->where('collection_name', 'images'))
                    ->defaultItems(0)
                    ->schema([
                        Placeholder::make('preview')
                            ->label('Immagine')
                            ->content(fn ($record) => $record ? new HtmlString("<img src='{$record->getUrl('thumbnail')}' class='h-20 w-auto rounded border shadow-sm'>") : 'Sconosciuta'),
                        Select::make('custom_properties.color_ids')
                            ->label('Associa a uno o più colori')
                            ->multiple()
                            ->options(fn ($record) => VariationOption::pluck('name', 'id'))
                            ->preload()
                            ->searchable()
                            ->columnSpan(2),
                        TextInput::make('custom_properties.alt')
                            ->label('Testo Alt')
                            ->placeholder('es. Vista laterale')
                            ->columnSpan(2),
                        Checkbox::make('custom_properties.is_manual')
                            ->label('Manuale')
                            ->inline(false),
                    ])
                    ->columns(3)
                    ->grid(2)
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false),
            ])
            ->columnSpanFull();
    }

    public static function getPrintPlacementsRepeater(): Repeater
    {
        return Repeater::make('printPlacements')
            ->relationship('printPlacements')
            ->defaultItems(0)
            ->label('Posizioni di Stampa')
            ->schema([
                Select::make('print_placement_id')
                    ->label('Posizione')
                    ->options(PrintPlacement::pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, $state) {
                        if ($state) {
                            $placement = PrintPlacement::where('id', '=', $state, 'and')->first();
                            if ($placement) {
                                $set('additional_price', $placement->default_price);
                            }
                        }
                    })
                    ->distinct()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                TextInput::make('additional_price')
                    ->label('Sovrapprezzo (€)')
                    ->numeric()
                    ->prefix('+ €')
                    ->default(0)
                    ->required(),
            ])
            ->columns(2)
            ->grid(2)
            ->addActionLabel('Aggiungi Posizione');
    }

    public static function getPrintSidesField(): Select
    {
        return Select::make('printSides')
            ->relationship('printSides', 'name')
            ->label('Lati di Stampa Disponibili')
            ->multiple()
            ->preload()
            ->searchable();
    }

    public static function getIsActiveField(): Toggle
    {
        return Toggle::make('is_active')
            ->label('Attivo (Visibile sul catalogo)')
            ->default(true)
            ->required();
    }

    public static function getIsFeaturedField(): Toggle
    {
        return Toggle::make('is_featured')
            ->label('Prodotto in Evidenza')
            ->default(false)
            ->required();
    }

    public static function getPricingModelField(): Select
    {
        return Select::make('pricing_model')
            ->label('Modello di Prezzo')
            ->options([
                'fixed' => 'Fisso (Prezzo base del catalogo)',
                'quantity' => 'A Scaglioni (Sconti per quantità)',
                'area' => 'A Metratura / Area (Prezzo per mq)',
            ])
            ->default('fixed')
            ->live()
            ->required();
    }

    public static function getMinAreaField(): TextInput
    {
        return TextInput::make('min_area')
            ->label('Area Minima Fatturabile (mq)')
            ->numeric()
            ->default(0.1)
            ->step(0.01)
            ->placeholder('es. 0.1')
            ->visible(fn (Get $get): bool => $get('pricing_model') === 'area');
    }
}
