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
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|UnitEnum|null $navigationGroup = 'Ordini';

    protected static ?string $navigationLabel = 'Ordini';

    protected static ?string $modelLabel = 'Ordine';

    protected static ?string $pluralModelLabel = 'Ordini';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
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
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('work_status', '=', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('work_status', '=', 'pending')->count() > 0 ? 'warning' : 'gray';
    }
}
