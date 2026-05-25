<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\ItemProducts;

use App\Enums\ProductClass;
use App\Filament\Resources\Products\ItemProducts\Pages\CreateItemProduct;
use App\Filament\Resources\Products\ItemProducts\Pages\EditItemProduct;
use App\Filament\Resources\Products\ItemProducts\Pages\ListItemProducts;
use App\Filament\Resources\Products\ItemProducts\RelationManagers\SkusRelationManager;
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

class ItemProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogo';

    protected static ?string $navigationLabel = 'Articoli Standard';

    protected static ?string $modelLabel = 'Articolo Standard';

    protected static ?string $pluralModelLabel = 'Articoli Standard';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema, ProductClass::ItemBased);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('product_class', ProductClass::ItemBased);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            SkusRelationManager::class,
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListItemProducts::route('/'),
            'create' => CreateItemProduct::route('/create'),
            'edit' => EditItemProduct::route('/{record}/edit'),
        ];
    }
}
