<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\AreaProducts;

use App\Enums\ProductClass;
use App\Filament\Resources\Products\AreaProducts\Pages\CreateAreaProduct;
use App\Filament\Resources\Products\AreaProducts\Pages\EditAreaProduct;
use App\Filament\Resources\Products\AreaProducts\Pages\ListAreaProducts;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;
use UnitEnum;

class AreaProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogo';

    protected static ?string $navigationLabel = 'Grande Formato';

    protected static ?string $modelLabel = 'Prodotto Grande Formato';

    protected static ?string $pluralModelLabel = 'Prodotti Grande Formato';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema, ProductClass::AreaBased);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('product_class', ProductClass::AreaBased);
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
            'index' => ListAreaProducts::route('/'),
            'create' => CreateAreaProduct::route('/create'),
            'edit' => EditAreaProduct::route('/{record}/edit'),
        ];
    }
}
