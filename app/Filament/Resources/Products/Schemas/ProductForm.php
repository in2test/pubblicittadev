<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductClass;
use App\Models\Category;
use App\Models\PrintPlacement;
use App\Models\PrintSide;
use App\Models\Product;
use App\Models\VariationOption;
use App\Models\VariationType;
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
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * ProductForm
 *
 * Provides the central Filament form schema for managing products.
 * This class abstracts the form definition so it can be reused across different
 * product resource types (like Standard Products vs Variable Products).
 */
class ProductForm
{
    /**
     * Main configuration method that returns the complete form schema.
     * The form is divided into several Tabs: Generale, Varianti & Scaglioni,
     * Personalizzazione Stampa, and Galleria Immagini.
     *
     * @param  Schema  $schema  The base Filament schema.
     */
    public static function configure(Schema $schema, ?ProductClass $productClass = null): Schema
    {
        return $schema
            ->components([
                static::getTypeField(), // Campo nascosto

                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Generale')
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 2])->schema([
                                    Section::make('Informazioni Principali')
                                        ->schema([
                                            static::getNameField(),
                                            static::getSlugField(),
                                            static::getSkuField(),
                                            static::getCategoryField(),
                                            static::getDescriptionField(),
                                        ]),
                                    Section::make('Prezzi e Stato')
                                        ->schema([
                                            static::getIsActiveField(),
                                            static::getIsFeaturedField(),
                                            static::getPricingModelField(),
                                            static::getPriceField(),
                                            static::getOfferPriceField(),
                                            static::getMinAreaField(),
                                            static::getMaxWidthField(),
                                            static::getMaxHeightField(),
                                        ]),
                                    Section::make('Ottimizzazione Resa (Fogli e Misure)')
                                        ->visible(fn () => $productClass === ProductClass::ItemBased || ! $productClass instanceof ProductClass)
                                        ->schema([
                                            Grid::make(2)->schema([
                                                TextInput::make('sheet_width')
                                                    ->label('Larghezza Foglio di Stampa (mm)')
                                                    ->numeric()
                                                    ->step(0.01)
                                                    ->placeholder('es. 32'),
                                                TextInput::make('sheet_height')
                                                    ->label('Altezza Foglio di Stampa (mm)')
                                                    ->numeric()
                                                    ->step(0.01)
                                                    ->placeholder('es. 45'),
                                            ]),
                                            Toggle::make('allows_custom_size')
                                                ->label('Accetta misure non standard (Formato Personalizzato)')
                                                ->live()
                                                ->default(false),
                                            Grid::make(2)
                                                ->visible(fn (Get $get): bool => $get('allows_custom_size') === true)
                                                ->schema([
                                                    TextInput::make('min_custom_width')
                                                        ->label('Base Minima (cm)')
                                                        ->numeric()
                                                        ->step(0.01),
                                                    TextInput::make('max_custom_width')
                                                        ->label('Base Massima (cm)')
                                                        ->numeric()
                                                        ->step(0.01),
                                                    TextInput::make('min_custom_height')
                                                        ->label('Altezza Minima (cm)')
                                                        ->numeric()
                                                        ->step(0.01),
                                                    TextInput::make('max_custom_height')
                                                        ->label('Altezza Massima (cm)')
                                                        ->numeric()
                                                        ->step(0.01),
                                                ]),
                                        ]),
                                ]),
                            ]),

                        Tab::make('Varianti & Scaglioni')
                            ->icon('heroicon-m-squares-2x2')
                            ->visible(fn () => $productClass !== ProductClass::AreaBased)
                            ->schema([
                                static::getVariationTypesRepeater(),
                                static::getSkusRepeater()
                                    ->visible(fn () => $productClass !== ProductClass::ItemBased),
                                static::getPricingTiersRepeater()
                                    ->visible(fn () => $productClass !== ProductClass::ItemBased),
                            ]),

                        Tab::make('Personalizzazione Stampa')
                            ->icon('heroicon-m-printer')
                            ->schema([
                                static::getPersonalizationTypeField(),
                                static::getPrintSidesField(),
                                static::getPrintPlacementsRepeater(),
                            ]),

                        Tab::make('Galleria Immagini')
                            ->icon('heroicon-m-photo')
                            ->schema([
                                static::getImagesField(),
                                static::getColorGallerySection(),
                            ]),
                    ])
                    ->columnSpanFull(),
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

    public static function getVariationTypesRepeater(): Repeater
    {
        return Repeater::make('productVariationTypes')
            ->relationship('productVariationTypes')
            ->label('Varianti Base e Modificatori (Add-ons)')
            ->defaultItems(0)
            ->schema([
                Select::make('variation_type_id')
                    ->label('Tipo (es. Formato, Finitura)')
                    ->relationship('type', 'name')
                    ->required()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                Toggle::make('is_modifier')
                    ->label('Usa come Modificatore')
                    ->helperText('Se attivo, non moltiplica gli SKU, ma aggiunge un sovrapprezzo (es. +10% Plastificazione).')
                    ->live(),
                Toggle::make('has_images')
                    ->label('Variante Visiva (Colore)')
                    ->helperText('Attiva se cambia l\'immagine (es. Colore T-shirt).'),
                Repeater::make('options')
                    ->relationship('options')
                    ->label('Prezzi Modificatori')
                    ->visible(fn (Get $get) => $get('is_modifier') === true)
                    ->schema([
                        Select::make('variation_option_id')
                            ->label('Opzione')
                            ->options(function (Get $get) {
                                $variationTypeId = $get('../../variation_type_id');
                                if (! $variationTypeId) {
                                    return [];
                                }

                                return VariationOption::where('variation_type_id', $variationTypeId)->pluck('name', 'id');
                            })
                            ->required(),
                        Select::make('modifier_type')
                            ->label('Tipo di ricarico')
                            ->options([
                                'flat' => 'Fisso (€ a pezzo)',
                                'percentage' => 'Percentuale (%)',
                            ])
                            ->default('flat')
                            ->required(),
                        TextInput::make('price_modifier')
                            ->label('Valore (€ o %)')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(3),
            ])
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => VariationType::find($state['variation_type_id'] ?? null)?->name ?? null);
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

    /**
     * Configures the repeater for Quantity-based discount tiers (Prezzi a Scaglioni).
     * Includes complex calculation logic via the cascadePricingTiers method to automate
     * unit and total price calculations as quantities change.
     */
    public static function getPricingTiersRepeater(): Repeater
    {
        return Repeater::make('pricingTiers')
            ->relationship('pricingTiers')
            ->defaultItems(0)
            ->label('Prezzi a Scaglioni (Sconti per Quantità)')
            ->schema([
                TextInput::make('min_quantity')
                    ->label('Quantità')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                        $qty = (int) $state;
                        $unit = (float) $get('price_per_unit');
                        if ($qty > 0 && $unit > 0) {
                            $set('total_price', round($qty * $unit, 2));
                        }
                    }),
                Toggle::make('is_custom_price')
                    ->label('Prezzo bloccato / Sovrascritto')
                    ->default(false)
                    ->live(),
                TextInput::make('total_price')
                    ->label('Prezzo Totale (€)')
                    ->numeric()
                    ->prefix('€')
                    ->dehydrated(false)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, $state, $component) {
                        $qty = (int) $get('min_quantity');
                        if ($qty > 0 && is_numeric($state)) {
                            $unitPrice = (float) $state / $qty;
                            $set('price_per_unit', round($unitPrice, 4));
                            $set('is_custom_price', true);

                            // Trigger cascade calculation
                            static::cascadePricingTiers($component);
                        }
                    })
                    ->afterStateHydrated(function (TextInput $component, Get $get, Set $set) {
                        $qty = (int) $get('min_quantity');
                        $unit = (float) $get('price_per_unit');
                        if ($qty > 0 && $unit > 0) {
                            $set('total_price', round($qty * $unit, 2));
                        }
                    }),
                TextInput::make('price_per_unit')
                    ->label('Prezzo Unitario (€)')
                    ->numeric()
                    ->prefix('€')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, $state, $component) {
                        $qty = (int) $get('min_quantity');
                        $unit = (float) $state;
                        if ($qty > 0 && $unit > 0) {
                            $set('total_price', round($qty * $unit, 2));
                            $set('is_custom_price', true);

                            // Trigger cascade calculation
                            static::cascadePricingTiers($component);
                        }
                    }),
                Select::make('print_side_id')
                    ->label('Lato di Stampa Associato (Opzionale per Biglietti da Visita)')
                    ->options(fn () => PrintSide::pluck('name', 'id'))
                    ->nullable()
                    ->searchable()
                    ->preload(),
            ])
            ->columns(2)
            ->grid(1)
            ->addActionLabel('Aggiungi Scaglione di Prezzo');
    }

    /**
     * Calculates the cascading logic for tier prices when a user inputs a total or unit price.
     * This method automatically interpolates or extrapolates missing tier prices based on
     * the defined or locked prices in other tiers.
     *
     * @param  mixed  $component  The Filament component triggering the cascade.
     */
    protected static function cascadePricingTiers($component): void
    {
        // $component->getContainer()->getParentComponent() gets the repeater
        $repeater = $component->getContainer()->getParentComponent();
        if (! $repeater instanceof Repeater) {
            return;
        }

        // We get the full state of the repeater
        // The path to the repeater state can be found via the component's state path
        $statePath = $repeater->getStatePath();

        // Use livewire directly since we need to modify siblings
        $livewire = $component->getLivewire();
        $tiers = data_get($livewire, $statePath);

        if (! is_array($tiers) || count($tiers) < 2) {
            return;
        }

        // Sort by min_quantity to calculate correctly
        uasort($tiers, fn ($a, $b) => (int) ($a['min_quantity'] ?? 0) <=> (int) ($b['min_quantity'] ?? 0));

        $keys = array_keys($tiers);
        $lockedIndices = [];

        foreach ($keys as $i => $key) {
            if (! empty($tiers[$key]['is_custom_price']) || $i === 0) {
                // Ensure at least the first item is considered a baseline if nothing is locked
                $lockedIndices[] = $i;
            }
        }

        foreach ($keys as $i => $key) {
            if (in_array($i, $lockedIndices)) {
                continue;
            }

            $beforeIdx = null;
            $afterIdx = null;

            foreach (array_reverse($lockedIndices) as $li) {
                if ($li < $i) {
                    $beforeIdx = $li;
                    break;
                }
            }

            foreach ($lockedIndices as $li) {
                if ($li > $i) {
                    $afterIdx = $li;
                    break;
                }
            }

            $qty = (int) ($tiers[$key]['min_quantity'] ?? 0);
            $qtyBefore = (int) ($tiers[$keys[$beforeIdx]]['min_quantity'] ?? 0);
            $priceBefore = (float) ($tiers[$keys[$beforeIdx]]['price_per_unit'] ?? 0);

            if ($afterIdx !== null) {
                // Interpolate
                $qtyAfter = (int) ($tiers[$keys[$afterIdx]]['min_quantity'] ?? 0);
                $priceAfter = (float) ($tiers[$keys[$afterIdx]]['price_per_unit'] ?? 0);

                if ($qtyAfter > $qtyBefore) {
                    $ratio = ($qty - $qtyBefore) / ($qtyAfter - $qtyBefore);
                    $interpolatedPrice = $priceBefore + ($priceAfter - $priceBefore) * $ratio;
                    $tiers[$key]['price_per_unit'] = round($interpolatedPrice, 4);
                }
            } else {
                // Extrapolate (-10% from the preceding tier)
                $prevPrice = (float) ($tiers[$keys[$i - 1]]['price_per_unit'] ?? 0);
                $tiers[$key]['price_per_unit'] = round($prevPrice * 0.90, 4);
            }

            // Update total price for this tier
            $tiers[$key]['total_price'] = round($qty * $tiers[$key]['price_per_unit'], 2);
        }

        data_set($livewire, $statePath, $tiers);
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

    public static function getPricingModelField(): Hidden
    {
        // Pricing model is now derived from product_class, so we can hide this or remove it entirely.
        // For backwards compatibility, we default to 'fixed' but it's no longer the primary source of truth.
        return Hidden::make('pricing_model')
            ->default('fixed');
    }

    public static function getMinAreaField(): TextInput
    {
        return TextInput::make('min_area')
            ->label('Area Minima Fatturabile (mq)')
            ->numeric()
            ->default(0.1)
            ->step(0.01)
            ->placeholder('es. 0.1');
    }

    public static function getMaxWidthField(): TextInput
    {
        return TextInput::make('max_width')
            ->label('Larghezza Massima Foglio (mm)')
            ->numeric()
            ->step(0.01)
            ->placeholder('es. 300 per un foglio 3×2 m')
            ->helperText('Lascia vuoto se illimitato su questo asse.');
    }

    public static function getMaxHeightField(): TextInput
    {
        return TextInput::make('max_height')
            ->label('Altezza Massima Foglio (mm)')
            ->numeric()
            ->step(0.01)
            ->placeholder('es. 200 per un foglio 3×2 m')
            ->helperText('Lascia vuoto se illimitato su questo asse.');
    }
}
