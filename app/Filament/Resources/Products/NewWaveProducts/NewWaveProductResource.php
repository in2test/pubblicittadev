<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\NewWaveProducts;

use App\Filament\Resources\Products\NewWaveProducts\Pages\CreateNewWaveProduct;
use App\Filament\Resources\Products\NewWaveProducts\Pages\EditNewWaveProduct;
use App\Filament\Resources\Products\NewWaveProducts\Pages\ListNewWaveProducts;
use App\Filament\Resources\Products\Schemas\NewWaveProductForm;
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

class NewWaveProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogo';

    protected static ?string $navigationLabel = 'Prodotti NewWave';

    protected static ?string $modelLabel = 'Prodotto NewWave';

    protected static ?string $pluralModelLabel = 'Prodotti NewWave';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloudArrowDown;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return NewWaveProductForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', Product::TYPE_NEWWAVE);
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
            'index' => ListNewWaveProducts::route('/'),
            'create' => CreateNewWaveProduct::route('/create'),
            'edit' => EditNewWaveProduct::route('/{record}/edit'),
        ];
    }
}
