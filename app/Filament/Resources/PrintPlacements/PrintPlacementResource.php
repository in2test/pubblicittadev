<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrintPlacements;

use App\Filament\Resources\PrintPlacements\Pages\CreatePrintPlacement;
use App\Filament\Resources\PrintPlacements\Pages\EditPrintPlacement;
use App\Filament\Resources\PrintPlacements\Pages\ListPrintPlacements;
use App\Filament\Resources\PrintPlacements\Schemas\PrintPlacementForm;
use App\Filament\Resources\PrintPlacements\Tables\PrintPlacementsTable;
use App\Models\PrintPlacement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class PrintPlacementResource extends Resource
{
    protected static ?string $model = PrintPlacement::class;

    protected static string|UnitEnum|null $navigationGroup = 'Impostazioni Stampa';

    protected static ?string $navigationLabel = 'Posizioni di Stampa';

    protected static ?string $modelLabel = 'Posizione di Stampa';

    protected static ?string $pluralModelLabel = 'Posizioni di Stampa';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return PrintPlacementForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return PrintPlacementsTable::configure($table);
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
            'index' => ListPrintPlacements::route('/'),
            'create' => CreatePrintPlacement::route('/create'),
            'edit' => EditPrintPlacement::route('/{record}/edit'),
        ];
    }
}
