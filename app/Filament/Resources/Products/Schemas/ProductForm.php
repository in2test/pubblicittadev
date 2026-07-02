<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ModifierType;
use App\Enums\ProductClass;
use App\Models\Category;
use App\Models\Product;
use App\Models\VariationOption;
use App\Models\VariationType;
use App\Support\SlugGenerator;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
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
 * Variations are split into two clearly-labelled sections:
 *
 * - "Varianti Base" (is_modifier=false): determine SKU / price tier.
 *   Each option group can have its own images via ProductVariationType media.
 * - "Modificatori di Prezzo" (is_modifier=true): add a flat or percentage
 *   surcharge on top of the base price. Inherits a global default from
 *   VariationOption; per-product overrides can be set inline.
 */
class ProductForm
{
    /**
     * Main configuration method that returns the complete form schema.
     */
    public static function configure(Schema $schema, ?ProductClass $productClass = null): Schema
    {
        return $schema
            ->components([
                static::getTypeField(),

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
                                        ]),
                                    Section::make('Dettagli Estesi')
                                        ->schema([
                                            RichEditor::make('technical_specs')
                                                ->label('Specifiche Tecniche')
                                                ->columnSpanFull(),
                                            RichEditor::make('certifications')
                                                ->label('Certificazioni')
                                                ->columnSpanFull(),
                                            RichEditor::make('construction_features')
                                                ->label('Caratteristiche Costruttive')
                                                ->columnSpanFull(),
                                            RichEditor::make('customization_notes')
                                                ->label('Note di Personalizzazione')
                                                ->columnSpanFull(),
                                        ]),
                                    Section::make('Ottimizzazione Resa (Fogli e Misure)')
                                        ->visible(fn () => $productClass !== ProductClass::Apparel)
                                        ->schema([
                                            Grid::make(2)->schema([
                                                static::getSheetWidthField(),
                                                static::getSheetHeightField(),
                                            ]),
                                            Toggle::make('allows_custom_size')
                                                ->label('Accetta misure non standard (Formato Personalizzato)')
                                                ->live()
                                                ->default(false),
                                            Grid::make(2)
                                                ->visible(fn (Get $get): bool => $get('allows_custom_size') === true)
                                                ->schema([
                                                    TextInput::make('min_custom_width')
                                                        ->label('Base Minima (mm)')
                                                        ->numeric()
                                                        ->step(0.01),
                                                    TextInput::make('max_custom_width')
                                                        ->label('Base Massima (mm)')
                                                        ->numeric()
                                                        ->step(0.01),
                                                    TextInput::make('min_custom_height')
                                                        ->label('Altezza Minima (mm)')
                                                        ->numeric()
                                                        ->step(0.01),
                                                    TextInput::make('max_custom_height')
                                                        ->label('Altezza Massima (mm)')
                                                        ->numeric()
                                                        ->step(0.01),
                                                ]),
                                        ]),
                                ]),
                            ]),

                        Tab::make('Varianti')
                            ->icon('heroicon-m-squares-2x2')
                            ->visible(fn () => $productClass !== ProductClass::AreaBased)
                            ->schema([
                                static::getBaseVariationsRepeater(),
                                static::getModifiersRepeater(),
                                static::getPricingTiersRepeater()
                                    ->visible(fn () => $productClass !== ProductClass::ItemBased),
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

    public static function getPriceField(): TextInput
    {
        return TextInput::make('price')
            ->label('Prezzo Base (€)')
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

    /**
     * BASE VARIATIONS repeater (is_modifier = false).
     *
     * These define the price structure of the product (e.g. Thickness → 5mm, 10mm).
     * Each variation group can carry its own gallery images via the ProductVariationType
     * Spatie Media collection "option_images".
     */
    public static function getBaseVariationsRepeater(): Repeater
    {
        return Repeater::make('baseVariationTypes')
            ->relationship('baseVariationTypes')
            ->label('Varianti Base')
            ->helperText('Queste varianti definiscono la struttura di prezzo del prodotto (es. Spessore: 5mm, 10mm; Modello roll-up). Ogni opzione può avere le proprie foto.')
            ->defaultItems(0)
            ->reorderable(true)
            ->orderColumn('sort_order')
            ->schema([
                Hidden::make('is_modifier')->default(false),
                Select::make('variation_type_id')
                    ->label('Tipo di variante (es. Spessore, Modello)')
                    ->relationship('type', 'name')
                    ->required()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                Toggle::make('has_images')
                    ->label('Variante Visiva (cambia le immagini del prodotto)')
                    ->helperText('Attiva se ogni opzione ha un aspetto visivo diverso (es. colore, materiale).'),
                TextInput::make('sort_order')
                    ->label('Ordinamento')
                    ->numeric()
                    ->default(0)
                    ->columnSpan(1),
                Section::make('Immagini per Opzione')
                    ->description('Carica immagini specifiche per ogni opzione di questa variante (es. foto per Forex 5mm, foto diverse per Forex 10mm).')
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('optionImages')
                            ->relationship('options')
                            ->label('Galleria per Opzione')
                            ->defaultItems(0)
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
                                    ->required()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                            ])
                            ->addActionLabel('Aggiungi Opzione')
                            ->reorderable(false),
                    ]),
            ])
            ->collapsible()
            ->itemLabel(function (array $state): ?string {
                /** @var VariationType|null $type */
                $type = VariationType::find($state['variation_type_id'] ?? null);

                return $type ? $type->name : null;
            })
            ->addActionLabel('Aggiungi Variante Base');
    }

    /**
     * MODIFIERS repeater (is_modifier = true).
     *
     * These add a flat (€/pezzo) or percentage surcharge on the base price.
     * Each option inherits its modifier from the global VariationOption default;
     * the admin can override the value per-product by entering a custom amount.
     * Leaving the field blank (null) resets to the global default.
     */
    public static function getModifiersRepeater(): Repeater
    {
        return Repeater::make('modifierVariationTypes')
            ->relationship('modifierVariationTypes')
            ->label('Modificatori di Prezzo')
            ->helperText('Questi aggiungono un sovrapprezzo al totale (es. Stampa Fronte+Retro +25%, Plastificazione +10%). Il valore di default è globale; qui puoi sovrascriverlo per questo prodotto.')
            ->defaultItems(0)
            ->reorderable(true)
            ->orderColumn('sort_order')
            ->schema([
                Hidden::make('is_modifier')->default(true),
                Select::make('variation_type_id')
                    ->label('Tipo di modificatore (es. Lati di Stampa, Finitura)')
                    ->relationship('type', 'name')
                    ->required()
                    ->live()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                TextInput::make('sort_order')
                    ->label('Ordinamento')
                    ->numeric()
                    ->default(0),
                Repeater::make('options')
                    ->relationship('options')
                    ->label('Opzioni e Sovrapprezzi')
                    ->defaultItems(0)
                    ->columnSpanFull()
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
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    /** @var VariationOption|null $option */
                                    $option = VariationOption::find($state);
                                    if ($option) {
                                        // Pre-fill with global default (user can override)
                                        // @phpstan-ignore-next-line
                                        $set('modifier_type', $option->default_modifier_type?->value ?? 'flat');
                                        $set('price_modifier', $option->default_price_modifier > 0 ? (float) $option->default_price_modifier : null);
                                    }
                                }
                            })
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                        Select::make('modifier_type')
                            ->label('Tipo di ricarico')
                            ->options(ModifierType::class)
                            ->default(ModifierType::Flat->value)
                            ->required(),
                        TextInput::make('price_modifier')
                            ->label('Valore (null = usa default globale)')
                            ->helperText(function (Get $get): ?string {
                                $optionId = $get('variation_option_id');
                                if (! $optionId) {
                                    return null;
                                }

                                /** @var VariationOption|null $option */
                                $option = VariationOption::find($optionId);
                                if (! $option || $option->default_price_modifier <= 0) {
                                    return 'Nessun default globale impostato.';
                                }

                                return "Default globale: {$option->default_price_modifier} ({$option->default_modifier_type->getLabel()})";
                            })
                            ->numeric()
                            ->placeholder('Lascia vuoto per usare il default globale')
                            ->nullable(),
                    ])
                    ->columns(3)
                    ->addActionLabel('Aggiungi Opzione'),
            ])
            ->collapsible()
            ->itemLabel(function (array $state): ?string {
                /** @var VariationType|null $type */
                $type = VariationType::find($state['variation_type_id'] ?? null);

                return $type ? $type->name : null;
            })
            ->addActionLabel('Aggiungi Modificatore');
    }

    /**
     * Configures the repeater for Quantity-based discount tiers (Prezzi a Scaglioni).
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

                            static::cascadePricingTiers($component);
                        }
                    }),
            ])
            ->columns(2)
            ->grid(1)
            ->addActionLabel('Aggiungi Scaglione di Prezzo');
    }

    /**
     * Calculates the cascading logic for tier prices when a user inputs a total or unit price.
     *
     * @param  mixed  $component  The Filament component triggering the cascade.
     */
    protected static function cascadePricingTiers($component): void
    {
        $repeater = $component->getContainer()->getParentComponent();
        if (! $repeater instanceof Repeater) {
            return;
        }

        $statePath = $repeater->getStatePath();
        $livewire = $component->getLivewire();
        $tiers = data_get($livewire, $statePath);

        if (! is_array($tiers) || count($tiers) < 2) {
            return;
        }

        uasort($tiers, fn ($a, $b) => (int) ($a['min_quantity'] ?? 0) <=> (int) ($b['min_quantity'] ?? 0));

        $keys = array_keys($tiers);
        $lockedIndices = [];

        foreach ($keys as $i => $key) {
            if (! empty($tiers[$key]['is_custom_price']) || $i === 0) {
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
            $qtyBefore = $beforeIdx !== null ? (int) ($tiers[$keys[$beforeIdx]]['min_quantity'] ?? 0) : 0;
            $priceBefore = $beforeIdx !== null ? (float) ($tiers[$keys[$beforeIdx]]['price_per_unit'] ?? 0) : 0;

            if ($afterIdx !== null) {
                $qtyAfter = (int) ($tiers[$keys[$afterIdx]]['min_quantity'] ?? 0);
                $priceAfter = (float) ($tiers[$keys[$afterIdx]]['price_per_unit'] ?? 0);

                if ($qtyAfter > $qtyBefore) {
                    $ratio = ($qty - $qtyBefore) / ($qtyAfter - $qtyBefore);
                    $interpolatedPrice = $priceBefore + ($priceAfter - $priceBefore) * $ratio;
                    $tiers[$key]['price_per_unit'] = round($interpolatedPrice, 4);
                }
            } else {
                $prevPrice = (float) ($tiers[$keys[$i - 1]]['price_per_unit'] ?? 0);
                $tiers[$key]['price_per_unit'] = round($prevPrice * 0.90, 4);
            }

            $tiers[$key]['total_price'] = round($qty * $tiers[$key]['price_per_unit'], 2);
        }

        if (is_string($statePath)) {
            data_set($livewire, $statePath, $tiers);
        }
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
                        Select::make('custom_properties.variation_option_ids')
                            ->label('Associa a una o più varianti')
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

    public static function getSheetWidthField(): TextInput
    {
        return TextInput::make('sheet_width')
            ->label('Larghezza Foglio di Stampa (mm)')
            ->numeric()
            ->step(0.01)
            ->placeholder('es. 320');
    }

    public static function getSheetHeightField(): TextInput
    {
        return TextInput::make('sheet_height')
            ->label('Altezza Foglio di Stampa (mm)')
            ->numeric()
            ->step(0.01)
            ->placeholder('es. 450');
    }
}
