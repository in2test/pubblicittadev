<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductVariations;

use App\Filament\Resources\ProductVariations\Pages\CreateProductVariation;
use App\Filament\Resources\ProductVariations\Pages\EditProductVariation;
use App\Filament\Resources\ProductVariations\Pages\ListProductVariations;
use App\Filament\Resources\ProductVariations\Schemas\ProductVariationForm;
use App\Filament\Resources\ProductVariations\Tables\ProductVariationsTable;
use App\Models\ProductVariation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class ProductVariationResource extends Resource
{
    protected static ?string $model = ProductVariation::class;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogo';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 10;

    #[Override]
    public static function getNavigationLabel(): string
    {
        return 'Tutte le Varianti';
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return 'Variante Prodotto';
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return 'Varianti Prodotto';
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return ProductVariationForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return ProductVariationsTable::configure($table);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListProductVariations::route('/'),
            'create' => CreateProductVariation::route('/create'),
            'edit' => EditProductVariation::route('/{record}/edit'),
        ];
    }
}
