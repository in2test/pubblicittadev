<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductClass;
use App\Models\Category;
use App\Models\Product;
use App\Support\SlugGenerator;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
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

/**
 * ProductForm
 *
 * Composes the product form schema from focused field builders.
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
                                ProductVariationFields::getBaseVariationsRepeater(),
                                ProductVariationFields::getModifiersRepeater(),
                                ProductVariationFields::getPricingTiersRepeater()
                                    ->visible(fn () => $productClass !== ProductClass::ItemBased),
                            ]),

                        Tab::make('Galleria Immagini')
                            ->icon('heroicon-m-photo')
                            ->schema([
                                ProductMediaFields::getImagesField(),
                                ProductMediaFields::getColorGallerySection(),
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
