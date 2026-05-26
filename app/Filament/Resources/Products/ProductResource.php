<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products;

use App\Enums\ModifierType;
use App\Enums\ProductClass;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
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

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogo';

    protected static ?string $navigationLabel = 'Amministrazione Prodotti';

    protected static ?string $modelLabel = 'Prodotto';

    protected static ?string $pluralModelLabel = 'Amministrazione Prodotti';

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

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
                            ]),

                        Tab::make('2. Galleria e Varianti')
                            ->icon('heroicon-m-photo')
                            ->schema([
                                static::getImagesField(),
                                static::getBaseVariationsRepeater(),
                            ]),

                        Tab::make('3. Prezzi Varianti')
                            ->icon('heroicon-m-currency-euro')
                            ->schema([
                                static::getSkusRepeater(),
                            ]),

                        Tab::make('4. Associa Immagini')
                            ->icon('heroicon-m-squares-plus')
                            ->schema(static::getAssignImagesSection()),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        // View all standard products (not synced remote ones from NewWave unless desired, but standard administration is for our products)
        return parent::getEloquentQuery();
    }

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

                $words = preg_split('/[\s\-\_\,]+/', $state ?? '');
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
            ->label('Codice Prodotto Base')
            ->required();
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
            ]);
    }

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
                if ($state === 'area_based') {
                    $set('pricing_model', 'area');
                } elseif ($state === 'item_based') {
                    $set('pricing_model', 'quantity');
                } else {
                    $set('pricing_model', 'fixed');
                }
            });
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

    public static function getIsActiveField(): Toggle
    {
        return Toggle::make('is_active')
            ->label('Attivo (Visibile nel catalogo)')
            ->default(true);
    }

    public static function getIsFeaturedField(): Toggle
    {
        return Toggle::make('is_featured')
            ->label('Prodotto in Evidenza')
            ->default(false);
    }

    public static function getDescriptionField(): Textarea
    {
        return Textarea::make('description')
            ->label('Descrizione')
            ->rows(5)
            ->columnSpanFull();
    }

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
                    ->afterStateUpdated(function (Set $set, $state) {
                        if ($state) {
                            $type = VariationType::find($state);
                            if ($type && $type->default_modifier_type) {
                                $set('impact_type', $type->default_modifier_type);
                                $set('is_modifier', $type->default_modifier_type !== 'redefine');
                            }
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
                    ->afterStateUpdated(function (Set $set, $state) {
                        $set('is_modifier', $state !== 'redefine');
                    }),
                Hidden::make('is_modifier')
                    ->default(false),
                Repeater::make('options')
                    ->relationship('options')
                    ->orderColumn('sort_order')
                    ->reorderable(true)
                    ->label('Opzioni per questa Variante')
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
                            ->createOptionForm(function (Get $get) {
                                $variationTypeId = $get('../../variation_type_id');
                                $defaultType = 'flat';
                                $isModifier = false;
                                $isDimensions = false;
                                if ($variationTypeId) {
                                    $type = VariationType::find($variationTypeId);
                                    if ($type) {
                                        $isDimensions = $type->presentation_type === 'dimensions';
                                        if ($type->default_modifier_type) {
                                            $isModifier = in_array($type->default_modifier_type, ['flat', 'percentage']);
                                            if ($isModifier) {
                                                $defaultType = $type->default_modifier_type;
                                            }
                                        }
                                    }
                                }

                                return [
                                    TextInput::make('name')
                                        ->label('Nome Opzione')
                                        ->required(),
                                    TextInput::make('width')
                                        ->label('Larghezza (cm)')
                                        ->numeric()
                                        ->visible($isDimensions),
                                    TextInput::make('height')
                                        ->label('Altezza (cm)')
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
                                        ->visible(function () use ($variationTypeId) {
                                            if ($variationTypeId) {
                                                $type = VariationType::find($variationTypeId);

                                                return $type && $type->presentation_type === 'color_swatch';
                                            }

                                            return false;
                                        }),
                                ];
                            })
                            ->createOptionUsing(function (array $data, Get $get) {
                                return VariationOption::create([
                                    'variation_type_id' => $get('../../variation_type_id'),
                                    'name' => $data['name'],
                                    'value' => $data['name'],
                                    'default_modifier_type' => $data['default_modifier_type'] ?? 'flat',
                                    'default_price_modifier' => $data['default_price_modifier'] ?? 0.00,
                                    'width' => $data['width'] ?? null,
                                    'height' => $data['height'] ?? null,
                                    'color_hex' => $data['color_hex'] ?? null,
                                ])->id;
                            })
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                        TextInput::make('price_modifier')
                            ->label(fn (Get $get) => $get('../../impact_type') === 'percentage' ? 'Sovrapprezzo (%)' : 'Aggiunta (€)')
                            ->numeric()
                            ->prefix(fn (Get $get) => $get('../../impact_type') === 'flat' ? '€' : null)
                            ->suffix(fn (Get $get) => $get('../../impact_type') === 'percentage' ? '%' : null)
                            ->placeholder('Usa default globale')
                            ->nullable()
                            ->helperText(function (Get $get) {
                                $optionId = $get('variation_option_id');
                                if (! $optionId) {
                                    return null;
                                }

                                $option = VariationOption::find($optionId);
                                if (! $option || ! $option->default_price_modifier) {
                                    return 'Nessun default globale impostato.';
                                }

                                $symbol = $option->default_modifier_type?->value === 'percentage' ? '%' : '€';

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
            ->itemLabel(fn (array $state): ?string => ($type = VariationType::find($state['variation_type_id'] ?? null)) ? $type->name : null)
            ->addActionLabel('Aggiungi Variante');
    }

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
                    ->default(100),
                Toggle::make('is_available')
                    ->label('Disponibile')
                    ->default(true),
                Select::make('options')
                    ->label('Opzioni Associate')
                    ->relationship('options', 'name')
                    ->multiple()
                    ->preload()
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
                        if (empty($optionIds)) {
                            return VariationOption::pluck('name', 'id');
                        }

                        return VariationOption::whereIn('id', $optionIds)->pluck('name', 'id');
                    })
                    ->required(),
                Repeater::make('pricingTiers')
                    ->relationship('pricingTiers')
                    ->label('Sconti per Quantità (Scaglioni)')
                    ->schema([
                        TextInput::make('min_quantity')
                            ->label('Quantità Minima')
                            ->numeric()
                            ->required()
                            ->default(1),
                        TextInput::make('price_per_unit')
                            ->label('Prezzo Unitario (€)')
                            ->numeric()
                            ->required()
                            ->prefix('€'),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->addActionLabel('Aggiungi Scaglione'),
            ])
            ->columns(2)
            ->columnSpanFull()
            ->addActionLabel('Aggiungi SKU Variante');
    }

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
                            Select::make('custom_properties.color_ids')
                                ->label('Associa a una o più varianti')
                                ->multiple()
                                ->options(function () {
                                    return VariationOption::pluck('name', 'id');
                                })
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
}
