<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

/**
 * Resource class for managing Order models in the Filament admin panel.
 *
 * This class defines the navigation, form schema, table definition,
 * relations, and pages for the Order entity.
 */
class OrderResource extends Resource
{
    /**
     * The Eloquent model associated with this resource.
     */
    protected static ?string $model = Order::class;

    /**
     * The navigation group where this resource will be displayed.
     */
    protected static string|UnitEnum|null $navigationGroup = 'Ordini';

    /**
     * The label used for this resource in the navigation menu.
     */
    protected static ?string $navigationLabel = 'Ordini';

    /**
     * The singular label used for this resource.
     */
    protected static ?string $modelLabel = 'Ordine';

    /**
     * The plural label used for this resource.
     */
    protected static ?string $pluralModelLabel = 'Ordini';

    /**
     * The icon used for this resource in the navigation menu.
     */
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    /**
     * Configure the form schema for creating and editing orders.
     *
     * @param  Schema  $schema  The default schema instance.
     * @return Schema The configured schema instance.
     */
    #[Override]
    public static function form(Schema $schema): Schema
    {
        // Delegates form configuration to the OrderForm schema class
        return OrderForm::configure($schema);
    }

    /**
     * Configure the table for listing orders.
     *
     * @param  Table  $table  The default table instance.
     * @return Table The configured table instance.
     */
    #[Override]
    public static function table(Table $table): Table
    {
        // Delegates table configuration to the OrdersTable class
        return OrdersTable::configure($table);
    }

    /**
     * Get the list of relationship managers associated with this resource.
     *
     * @return array<class-string> The array of relation manager classes.
     */
    #[Override]
    public static function getRelations(): array
    {
        return [
            // No relationship managers defined yet.
        ];
    }

    /**
     * Get the list of pages registered for this resource.
     *
     * @return array<string, PageRegistration> The array of page routes.
     */
    #[Override]
    public static function getPages(): array
    {
        return [
            // Registering the list, create, and edit pages for the Order resource.
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }

    /**
     * Get the badge string displayed next to the resource navigation label.
     *
     * Calculates the number of orders with a 'pending' work status.
     *
     * @return string|null The badge value as a string.
     */
    public static function getNavigationBadge(): ?string
    {
        // Count all orders where the work status is pending
        return (string) static::getModel()::where('work_status', '=', 'pending')->count();
    }

    /**
     * Get the color of the navigation badge.
     *
     * Returns 'warning' (yellow/orange) if there are pending orders,
     * otherwise returns 'gray'.
     *
     * @return string|null The color of the badge.
     */
    public static function getNavigationBadgeColor(): ?string
    {
        // Check if there are any pending orders to determine the badge color
        return static::getModel()::where('work_status', '=', 'pending')->count() > 0 ? 'warning' : 'gray';
    }
}
