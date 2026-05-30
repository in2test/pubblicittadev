<?php

declare(strict_types=1);

namespace App\Filament\Resources\VariationTypes;

use App\Filament\Resources\VariationTypes\Pages\CreateVariationType;
use App\Filament\Resources\VariationTypes\Pages\EditVariationType;
use App\Filament\Resources\VariationTypes\Pages\ListVariationTypes;
use App\Filament\Resources\VariationTypes\Schemas\VariationTypeForm;
use App\Filament\Resources\VariationTypes\Tables\VariationTypesTable;
use App\Models\VariationType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class VariationTypeResource extends Resource
{
    protected static ?string $model = VariationType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogo';

    protected static ?string $modelLabel = 'Tipo Variante';

    protected static ?string $pluralModelLabel = 'Tipi Variante';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return VariationTypeForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return VariationTypesTable::configure($table);
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
            'index' => ListVariationTypes::route('/'),
            'create' => CreateVariationType::route('/create'),
            'edit' => EditVariationType::route('/{record}/edit'),
        ];
    }
}
