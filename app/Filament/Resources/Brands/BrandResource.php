<?php

declare(strict_types=1);

namespace App\Filament\Resources\Brands;

use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Filament\Resources\Brands\Schemas\BrandForm;
use App\Filament\Resources\Brands\Tables\BrandsTable;
use App\Models\Brand;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogo';

    protected static ?int $navigationSort = 0;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function getNavigationLabel(): string
    {
        return 'Brand';
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return 'Brand';
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return 'Brands';
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return BrandForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return BrandsTable::configure($table);
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
            'index' => ListBrands::route('/'),
            'create' => CreateBrand::route('/create'),
            'edit' => EditBrand::route('/{record}/edit'),
        ];
    }
}
