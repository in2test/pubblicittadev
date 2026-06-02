<?php

declare(strict_types=1);

namespace App\Filament\Resources\Transporters;

use App\Filament\Resources\Transporters\Pages\CreateTransporter;
use App\Filament\Resources\Transporters\Pages\EditTransporter;
use App\Filament\Resources\Transporters\Pages\ListTransporters;
use App\Filament\Resources\Transporters\Schemas\TransporterForm;
use App\Filament\Resources\Transporters\Tables\TransportersTable;
use App\Models\Transporter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class TransporterResource extends Resource
{
    protected static ?string $model = Transporter::class;

    protected static string|UnitEnum|null $navigationGroup = 'Impostazioni';

    protected static ?string $navigationLabel = 'Corrieri';

    protected static ?string $modelLabel = 'Corriere';

    protected static ?string $pluralModelLabel = 'Corrieri';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return TransporterForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return TransportersTable::configure($table);
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
            'index' => ListTransporters::route('/'),
            'create' => CreateTransporter::route('/create'),
            'edit' => EditTransporter::route('/{record}/edit'),
        ];
    }
}
