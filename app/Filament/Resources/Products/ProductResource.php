<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products;

use App\Enums\ModifierType;
use App\Enums\ProductClass;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Category;
use App\Models\Product;
use App\Models\VariationOption;
use App\Models\VariationType;
use App\Support\SlugGenerator;
use BackedEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Override;
use UnitEnum;

/**
 * Resource class for managing Products in the Filament administration panel.
 *
 * Handles the creation, editing, and listing of products, including their
 * categories, base settings, media (images), and complex pricing/variation logic.
 */
class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogo';

    protected static ?string $navigationLabel = 'Amministrazione Prodotti';

    protected static ?string $modelLabel = 'Prodotto';

    protected static ?string $pluralModelLabel = 'Amministrazione Prodotti';

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    /**
     * Define the form schema for creating and editing products.
     *
     * The form is divided into multiple tabs handling general information,
     * additional specifications, image gallery and variations, variant pricing,
     * and image associations.
     */
    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                static::getTypeField(),

                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('1. Informazioni Generali')
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                Grid::make(2)->schema([
                                    static::getNameField(),
                                    static::getSlugField(),
                                    static::getSkuField(),
                                    static::getCategoryField(),
                                    static::getProductClassField(),
                                    static::getPriceField(),
                                    static::getOfferPriceField(),
                                    static::getIsActiveField(),
                                    static::getIsFeaturedField(),
                                ]),
                                static::getDescriptionField(),
                                static::getSheetSettingsSection(),
                            ]),

                        Tab::make('2. Specifiche Aggiuntive')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                RichEditor::make('technical_specs')->label('Specifiche Tecniche')->columnSpanFull(),
                                RichEditor::make('certifications')->label('Certificazioni')->columnSpanFull(),
                                RichEditor::make('construction_features')->label('Caratteristiche Costruttive')->columnSpanFull(),
                                RichEditor::make('customization_notes')->label('Note per la Personalizzazione')->columnSpanFull(),
                            ]),

                        Tab::make('3. Galleria e Varianti')
                            ->icon('heroicon-m-photo')
                            ->schema([
                                static::getImagesField(),
                                static::getBaseVariationsRepeater(),
                            ]),

                        Tab::make('4. Prezzi Varianti')
                            ->icon('heroicon-m-currency-euro')
                            ->schema([
                                static::getSkusRepeater(),
                            ]),

                        Tab::make('5. Associa Immagini')
                            ->icon('heroicon-m-squares-plus')
                            ->schema(static::getAssignImagesSection()),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Define the table configuration for the resource index page.
     *
     * The table logic (columns, filters, actions) is delegated to the
     * external ProductsTable class to keep this resource class clean and focused.
     */
    #[Override]
    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    /**
     * Get the base Eloquent query builder for the resource.
     *
     * Ensures we only retrieve standard products for this administration view.
     */
    #[Override]
    public static function getEloquentQuery(): Builder
    {
        // View all standard products (not synced remote ones from NewWave unless desired, but standard administration is for our products)
        return parent::getEloquentQuery();
    }

    /**
     * Define the pages associated with this resource.
     *
     * @return array<string, PageRegistration>
     */
    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    // --- Core Schema Fields ---

    /**
     * Returns the hidden type field, defaulting to a standard product.
     */
    public static function getTypeField(): Hidden
    {
        return Hidden::make('type')
            ->default(Product::TYPE_STANDARD)
            ->dehydrated();
    }

    /**
     * Returns the product name field and auto-generates slug and SKU.
     */
    public static function getNameField(): TextInput
    {
        return TextInput::make('name')
            ->label('Nome Prodotto')
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, ?string $state, ?Model $record) {
                // Auto-generate the slug based on the product name
                $slug = SlugGenerator::unique(Product::class, $state, $record);
                $set('slug', $slug);

                // Auto-generate a basic SKU acronym from the name words
                $words = preg_split('/[\s\-\_\,]+/', $state ?? '') ?: [];
                $acronym = '';
                foreach ($words as $word) {
                    if (! empty($word)) {
                        $acronym .= mb_substr($word, 0, 1);
                    }
                }
                $set('sku', Str::upper($acronym));
            })
            ->required();
    }

    /**
     * Returns the product slug field.
     */
    public static function getSlugField(): TextInput
    {
        return TextInput::make('slug')
            ->label('Slug')
            ->unique(ignorable: fn ($record) => $record)
            ->required();
    }

    /**
     * Returns the base SKU field.
     */
    public static function getSkuField(): TextInput
    {
        return TextInput::make('sku')
            ->label('Codice Prodotto Base')
            ->required();
    }

    /**
     * Returns the category selection field with inline creation support.
     */
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
            ]);
    }

    /**
     * Returns the product class field determining the pricing calculation method.
     */
    public static function getProductClassField(): Select
    {
        return Select::make('product_class')
            ->label('Tipo Calcolo Prezzo')
            ->options([
                ProductClass::Apparel->value => 'Unitario (Abbigliamento)',
                ProductClass::AreaBased->value => 'A MQ (Metro Quadro)',
                ProductClass::ItemBased->value => 'Fasce di Prezzo (Scaglioni)',
            ])
            ->required()
            ->live()
            ->afterStateUpdated(function (Set $set, ?string $state) {
                // Automatically set the appropriate pricing model based on the product class
                if ($state === 'area_based') {
                    $set('pricing_model', 'area');
                } elseif ($state === 'item_based') {
                    $set('pricing_model', 'quantity');
                } else {
                    $set('pricing_model', 'fixed');
                }
            });
    }

    /**
     * Returns the base price field.
     */
    public static function getPriceField(): TextInput
    {
        return TextInput::make('price')
            ->label('Prezzo Base (€)')
            ->numeric()
            ->prefix('€');
    }

    /**
     * Returns the offer price field.
     */
    public static function getOfferPriceField(): TextInput
    {
        return TextInput::make('offer_price')
            ->label('Prezzo Offerta (€)')
            ->numeric()
            ->prefix('€');
    }

    /**
     * Returns the active toggle field.
     */
    public static function getIsActiveField(): Toggle
    {
        return Toggle::make('is_active')
            ->label('Attivo (Visibile nel catalogo)')
            ->default(true);
    }

    /**
     * Returns the featured toggle field.
     */
    public static function getIsFeaturedField(): Toggle
    {
        return Toggle::make('is_featured')
            ->label('Prodotto in Evidenza')
            ->default(false);
    }

    /**
     * Returns the description textarea field.
     */
    public static function getDescriptionField(): Textarea
    {
        return Textarea::make('description')
            ->label('Descrizione')
            ->rows(5)
            ->columnSpanFull();
    }

    /**
     * Returns the media library file upload field for product images.
     */
    public static function getImagesField(): SpatieMediaLibraryFileUpload
    {
        return SpatieMediaLibraryFileUpload::make('images')
            ->label('Galleria Immagini')
            ->collection('images')
            ->multiple()
            ->reorderable()
            ->panelLayout('grid')
            ->disk('public')
            ->columnSpanFull();
    }

    /**
     * Returns the repeater for base variations and options.
     *
     * This schema handles defining variation types (e.g., Color, Size) and their
     * specific options. It includes complex logic to determine how variations impact
     * the base price (modifiers vs redefining SKU prices).
     */
    public static function getBaseVariationsRepeater(): Repeater
    {
        return Repeater::make('productVariationTypes')
            ->relationship('productVariationTypes')
            ->orderColumn('sort_order')
            ->reorderable(true)
            ->label('Varianti Prodotto')
            ->helperText('Configura le varianti e definisci come ciascuna influisce sul prezzo.')
            ->defaultItems(0)
            ->schema([
                Select::make('variation_type_id')
                    ->label('Tipo Variante')
                    ->relationship('type', 'name')
                    ->required()
                    ->live()
                    // Set default modifier types when a variation type is selected
                    ->afterStateUpdated(function (Set $set, $state) {
                        /** @var VariationType|null $type */
                        $type = $state ? VariationType::find($state) : null;
                        if ($type && $type->default_modifier_type) {
                            $set('impact_type', $type->default_modifier_type);
                            // If it's not a redefine (which creates a new base price SKU), it acts as a price modifier
                            $set('is_modifier', $type->default_modifier_type !== 'redefine');
                        }
                    })
                    ->createOptionForm([
                        TextInput::make('name')->required(),
                        Select::make('presentation_type')
                            ->options([
                                'color_swatch' => 'Colore / Swatch',
                                'select' => 'Dropdown / Select',
                                'button' => 'Bottone / Scelta Singola',
                                'dimensions' => 'Dimensioni / Formato',
                            ])
                            ->default('select')
                            ->required(),
                        Select::make('default_modifier_type')
                            ->label('Impatto sul Prezzo di Default')
                            ->options([
                                'redefine' => 'Nuovi prezzi / scaglioni (Crea SKU)',
                                'flat' => 'Aggiunta fissa (+€)',
                                'percentage' => 'Percentuale (+%)',
                            ])
                            ->default('redefine')
                            ->required(),
                    ])
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                Toggle::make('has_images')
                    ->label('Questa variante influenza le immagini (es. Colore)'),
                Select::make('impact_type')
                    ->label('Impatto sul Prezzo')
                    ->options([
                        'redefine' => 'Nuovi prezzi / scaglioni (Crea SKU)',
                        'flat' => 'Aggiunta fissa (+€)',
                        'percentage' => 'Percentuale (+%)',
                    ])
                    ->default('redefine')
                    ->required()
                    ->live()
                    ->dehydrated(false)
                    // Hydrate impact_type based on whether the variant is a modifier and what the first option contains
                    ->afterStateHydrated(function (Set $set, $state, $record) {
                        if ($record) {
                            if (! $record->is_modifier) {
                                $set('impact_type', 'redefine');
                            } else {
                                $firstOption = $record->options()->first();
                                if ($firstOption && $firstOption->modifier_type === ModifierType::Percentage) {
                                    $set('impact_type', 'percentage');
                                } else {
                                    $set('impact_type', 'flat');
                                }
                            }
                        }
                    })
                    // Toggle is_modifier based on the chosen impact type
                    ->afterStateUpdated(function (Set $set, $state) {
                        $set('is_modifier', $state !== 'redefine');
                    }),
                Hidden::make('is_modifier')
                    ->default(false),

                // Nested Repeater for specific options within a variation type (e.g., Red, Blue for Color)
                Repeater::make('options')
                    ->relationship('options')
                    ->orderColumn('sort_order')
                    ->reorderable(true)
                    ->label('Opzioni per questa Variante')
                    ->schema([
                        Select::make('variation_option_id')
                            ->label('Opzione')
                            // Dynamically load options belonging to the parent variation type
                            ->options(fn (Get $get) => $get('../../variation_type_id') ? VariationOption::where('variation_type_id', $get('../../variation_type_id'))->pluck('name', 'id') : [])
                            ->required()
                            ->live()
                            ->createOptionForm(function (Get $get): array {
                                /** @var VariationType|null $type */
                                $type = $get('../../variation_type_id') ? VariationType::find($get('../../variation_type_id')) : null;

                                $isDimensions = $type?->presentation_type === 'dimensions';
                                $isModifier = in_array($type?->default_modifier_type, ['flat', 'percentage']);
                                $defaultType = ($isModifier && $type) ? $type->default_modifier_type : 'flat';

                                return [
                                    TextInput::make('name')
                                        ->label('Nome Opzione')
                                        ->required(),
                                    TextInput::make('width')
                                        ->label('Larghezza (mm)')
                                        ->numeric()
                                        ->visible($isDimensions),
                                    TextInput::make('height')
                                        ->label('Lunghezza (mm)')
                                        ->numeric()
                                        ->visible($isDimensions),
                                    Select::make('default_modifier_type')
                                        ->label('Tipo Impatto Prezzo')
                                        ->options([
                                            'flat' => 'Importo Fisso (€)',
                                            'percentage' => 'Percentuale (%)',
                                        ])
                                        ->default($defaultType)
                                        ->visible($isModifier),
                                    TextInput::make('default_price_modifier')
                                        ->label('Sovrapprezzo di Default')
                                        ->numeric()
                                        ->placeholder('es. 10 per 10% o 1.50 per €1.50')
                                        ->visible($isModifier),
                                    TextInput::make('color_hex')
                                        ->label('Hex Colore (es. #ff0000)')
                                        ->visible($type?->presentation_type === 'color_swatch'),
                                ];
                            })
                            // Create the related variation option directly from this form
                            ->createOptionUsing(fn (array $data, Get $get) => VariationOption::create([
                                'variation_type_id' => $get('../../variation_type_id'),
                                'name' => $data['name'],
                                'value' => $data['name'],
                                'default_modifier_type' => $data['default_modifier_type'] ?? 'flat',
                                'default_price_modifier' => $data['default_price_modifier'] ?? 0.00,
                                'width' => $data['width'] ?? null,
                                'height' => $data['height'] ?? null,
                                'color_hex' => $data['color_hex'] ?? null,
                            ])->id)
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                        TextInput::make('price_modifier')
                            ->label(fn (Get $get) => $get('../../impact_type') === 'percentage' ? 'Sovrapprezzo (%)' : 'Aggiunta (€)')
                            ->numeric()
                            ->prefix(fn (Get $get) => $get('../../impact_type') === 'flat' ? '€' : null)
                            ->suffix(fn (Get $get) => $get('../../impact_type') === 'percentage' ? '%' : null)
                            ->placeholder('Usa default globale')
                            ->nullable()
                            // Helper to display the global default modifier for this specific option
                            ->helperText(function (Get $get): ?string {
                                $optionId = $get('variation_option_id');
                                if (! $optionId) {
                                    return null;
                                }

                                /** @var VariationOption|null $option */
                                $option = VariationOption::find($optionId);
                                if (! $option || ! $option->default_price_modifier) {
                                    return 'Nessun default globale impostato.';
                                }

                                $modifierType = $option->default_modifier_type;
                                $symbol = $modifierType->value === 'percentage' ? '%' : '€';

                                return "Default globale: {$option->default_price_modifier}{$symbol}";
                            })
                            ->visible(fn (Get $get) => in_array($get('../../impact_type'), ['flat', 'percentage'])),
                        Hidden::make('modifier_type')
                            ->default(function (Get $get) {
                                $parentImpact = $get('../../impact_type');

                                return $parentImpact === 'percentage'
                                    ? ModifierType::Percentage->value
                                    : ModifierType::Flat->value;
                            }),
                    ])
                    ->columns(2)
                    ->addActionLabel('Aggiungi Opzione')
                    ->columnSpanFull(),
            ])
            ->collapsible()
            ->itemLabel(function (array $state): ?string {
                /** @var VariationType|null $type */
                $type = VariationType::find($state['variation_type_id'] ?? null);

                return $type ? $type->name : null;
            })
            ->addActionLabel('Aggiungi Variante');
    }

    /**
     * Returns the repeater for SKUs and specific variation pricing tiers.
     *
     * This section generates base SKU variations from the main product variations
     * (the ones marked as 'redefine'). It includes quantity discount tiers for
     * each SKU generated.
     */
    public static function getSkusRepeater(): Repeater
    {
        return Repeater::make('skus')
            ->relationship('skus')
            ->label('Prezzi e SKU Varianti')
            ->defaultItems(0)
            ->schema([
                TextInput::make('sku')
                    ->label('Codice SKU Variante')
                    ->required(),
                TextInput::make('override_price')
                    ->label('Prezzo di Override (€)')
                    ->numeric()
                    ->prefix('€'),
                TextInput::make('quantity')
                    ->label('Disponibilità Magazzino')
                    ->numeric()
                    ->default(-1)
                    ->nullable(),
                Toggle::make('is_available')
                    ->label('Disponibile')
                    ->default(true),
                Select::make('options')
                    ->label('Opzioni Associate')
                    ->relationship('options', 'name')
                    ->multiple()
                    ->preload()
                    // Filter available options based on those mapped in the base variations repeater
                    // that use the 'redefine' impact type (i.e., generate a new sku/price).
                    ->options(function (Get $get) {
                        $allVariations = $get('../../productVariationTypes') ?? [];
                        $optionIds = [];
                        foreach ($allVariations as $varType) {
                            $isModifier = $varType['is_modifier'] ?? false;
                            $impactType = $varType['impact_type'] ?? null;
                            if ($impactType === 'redefine' || (! $isModifier && $impactType === null)) {
                                $options = $varType['options'] ?? [];
                                foreach ($options as $opt) {
                                    if (! empty($opt['variation_option_id'])) {
                                        $optionIds[] = $opt['variation_option_id'];
                                    }
                                }
                            }
                        }
                        if ($optionIds === []) {
                            return VariationOption::pluck('name', 'id');
                        }

                        return VariationOption::whereIn('id', $optionIds)->pluck('name', 'id');
                    })
                    ->required()
                    ->live()
                    // Auto-generate the variant SKU suffix when an option is selected.
                    ->afterStateUpdated(function (Get $get, Set $set, ?array $state) {
                        $baseSku = $get('../../sku') ?? '';
                        if ($state === null || $state === []) {
                            $set('sku', $baseSku);

                            return;
                        }

                        // Order the options so the SKU is consistently generated.
                        $options = VariationOption::whereIn('id', $state)->get()->sortBy('sort_order');
                        $suffix = $options->map(function (VariationOption $option) {
                            $val = $option->value ?? $option->name;

                            return Str::slug($val);
                        })->implode('-');

                        $finalSku = $baseSku ? "{$baseSku}-".Str::upper($suffix) : Str::upper($suffix);
                        $set('sku', $finalSku);
                    }),
                // Inject the pricing tiers (quantity discounts) for this specific SKU variation
                ProductForm::getPricingTiersRepeater()
                    ->label('Sconti per Quantità (Scaglioni)')
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->columnSpanFull()
            ->addActionLabel('Aggiungi SKU Variante');
    }

    /**
     * Returns the schema for associating uploaded images with specific product variations.
     *
     * Uses media library to retrieve uploaded images and allows the admin to assign
     * them to specific variation options (e.g. a red shirt image to the "Red" color option).
     *
     * @return array<int, Component>
     */
    public static function getAssignImagesSection(): array
    {
        return [
            Placeholder::make('assign_images_notice')
                ->visible(fn ($record) => $record === null)
                ->content('Le immagini caricate potranno essere associate alle varianti una volta creato e salvato il prodotto.'),

            Section::make('Associazione Immagini a Varianti')
                ->visible(fn ($record) => $record !== null)
                ->schema([
                    Repeater::make('media')
                        ->relationship('media', fn ($query) => $query->where('collection_name', 'images'))
                        ->defaultItems(0)
                        ->schema([
                            Placeholder::make('preview')
                                ->label('Immagine')
                                ->content(fn ($record) => $record ? new HtmlString("<img src='{$record->getUrl('thumbnail')}' class='h-20 w-auto rounded border shadow-sm'>") : 'Nessuna immagine'),
                            Select::make('custom_properties.variation_option_ids')
                                ->label('Associa a una o più varianti')
                                ->multiple()
                                ->options(fn () => VariationOption::pluck('name', 'id'))
                                ->preload()
                                ->searchable()
                                ->columnSpan(2),
                            TextInput::make('custom_properties.alt')
                                ->label('Testo Alt')
                                ->placeholder('es. Vista laterale')
                                ->columnSpan(2),
                        ])
                        ->columns(3)
                        ->grid(2)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false),
                ]),
        ];
    }

    /**
     * Returns the section for configuring print sheet settings.
     *
     * Used for area-based products or items that require specific print dimensions.
     * Allows configuring max/min dimensions if custom sizes are allowed.
     */
    public static function getSheetSettingsSection(): Section
    {
        return Section::make('Ottimizzazione Resa (Fogli e Misure)')
            ->visible(fn (Get $get): bool => $get('product_class') !== ProductClass::Apparel->value)
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('sheet_width')
                        ->label('Larghezza Foglio di Stampa (mm)')
                        ->numeric()
                        ->step(0.01)
                        ->placeholder('es. 320'),
                    TextInput::make('sheet_height')
                        ->label('Altezza Foglio di Stampa (mm)')
                        ->numeric()
                        ->step(0.01)
                        ->placeholder('es. 450'),
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
            ]);
    }
}
