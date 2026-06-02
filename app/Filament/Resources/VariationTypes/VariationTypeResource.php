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

/**
 * Class VariationTypeResource
 *
 * This resource manages the VariationType models in the Filament admin panel.
 * It defines the navigation icon, group, and labels, while delegating the
 * actual form and table configurations to dedicated schema classes.
 */
class VariationTypeResource extends Resource
{
    /**
     * The underlying Eloquent model managed by this resource.
     */
    protected static ?string $model = VariationType::class;

    /**
     * The icon displayed in the navigation menu for this resource.
     */
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    /**
     * The group under which this resource will be nested in the navigation menu.
     */
    protected static string|UnitEnum|null $navigationGroup = 'Catalogo';

    /**
     * The singular label used to represent a single instance of the model.
     */
    protected static ?string $modelLabel = 'Tipo Variante';

    /**
     * The plural label used to represent multiple instances of the model.
     */
    protected static ?string $pluralModelLabel = 'Tipi Variante';

    /**
     * Configure the form schema for creating and editing Variation Types.
     * Note: The complex logic (e.g., the repeater for options, modifier toggles)
     * is delegated to the VariationTypeForm schema class.
     *
     * @param  Schema  $schema  The default form schema.
     * @return Schema The configured form schema.
     */
    #[Override]
    public static function form(Schema $schema): Schema
    {
        return VariationTypeForm::configure($schema);
    }

    /**
     * Configure the table schema for listing Variation Types.
     *
     * @param  Table  $table  The default table instance.
     * @return Table The configured table instance.
     */
    #[Override]
    public static function table(Table $table): Table
    {
        return VariationTypesTable::configure($table);
    }

    /**
     * Define the relationships available for this resource.
     *
     * @return array<class-string> An array of relation managers.
     */
    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Define the pages that make up this resource (e.g., index, create, edit).
     *
     * @return array<string, mixed> An array of page route definitions.
     */
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
