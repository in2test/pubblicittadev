<?php

namespace App\Filament\Resources\PrintSides;

use App\Filament\Resources\PrintSides\Pages\CreatePrintSide;
use App\Filament\Resources\PrintSides\Pages\EditPrintSide;
use App\Filament\Resources\PrintSides\Pages\ListPrintSides;
use App\Filament\Resources\PrintSides\Schemas\PrintSideForm;
use App\Filament\Resources\PrintSides\Tables\PrintSidesTable;
use App\Models\PrintSide;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PrintSideResource extends Resource
{
    protected static ?string $model = PrintSide::class;

    protected static string|UnitEnum|null $navigationGroup = 'Impostazioni Stampa';

    protected static ?string $navigationLabel = 'Lati di Stampa';

    protected static ?string $modelLabel = 'Lato di Stampa';

    protected static ?string $pluralModelLabel = 'Lati di Stampa';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare2Stack;

    public static function form(Schema $schema): Schema
    {
        return PrintSideForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrintSidesTable::configure($table);
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
            'index' => ListPrintSides::route('/'),
            'create' => CreatePrintSide::route('/create'),
            'edit' => EditPrintSide::route('/{record}/edit'),
        ];
    }
}
