<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products;

use App\Enums\ProductClass;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\SkusRelationManager;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Category;
use App\Models\Product;
use App\Models\VariationOption;
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

    public static function getRelations(): array
    {
        return [
            SkusRelationManager::class,
        ];
    }

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
     */
    public static function getBaseVariationsRepeater(): Repeater
    {
        return ProductResourceBaseVariations::make();
    }

    /**
     * Returns the repeater for SKUs and their quantity pricing.
     */
    public static function getSkusRepeater(): Repeater
    {
        return ProductResourceSkus::make();
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
