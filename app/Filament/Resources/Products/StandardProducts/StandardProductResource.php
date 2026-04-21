<?php

namespace App\Filament\Resources\Products\StandardProducts;

use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\StandardProducts\Pages\CreateStandardProduct;
use App\Filament\Resources\Products\StandardProducts\Pages\EditStandardProduct;
use App\Filament\Resources\Products\StandardProducts\Pages\ListStandardProducts;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class StandardProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogo';

    protected static ?string $navigationLabel = 'Prodotti Standard';

    protected static ?string $modelLabel = 'Prodotto Standard';

    protected static ?string $pluralModelLabel = 'Prodotti Standard';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', Product::TYPE_STANDARD);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStandardProducts::route('/'),
            'create' => CreateStandardProduct::route('/create'),
            'edit' => EditStandardProduct::route('/{record}/edit'),
        ];
    }
}
