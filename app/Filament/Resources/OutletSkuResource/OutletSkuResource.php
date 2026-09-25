<?php

declare(strict_types=1);

namespace App\Filament\Resources\OutletSkuResource;

use App\Filament\Resources\OutletSkuResource\Pages\ListOutletSkus;
use App\Filament\Resources\OutletSkuResource\Tables\OutletSkusTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

/**
 * Resource class for managing outlet products in the Filament admin panel.
 *
 * Displays Product records and allows per-product outlet configuration
 * (activating outlet mode and setting override prices per SKU variant).
 * Table logic is delegated to OutletSkusTable.
 */
class OutletSkuResource extends Resource
{
    /**
     * The Eloquent model associated with this resource.
     */
    protected static ?string $model = Product::class;

    /**
     * The navigation group where this resource will be displayed.
     */
    protected static string|UnitEnum|null $navigationGroup = 'Catalogo';

    /**
     * The sort order of this resource within its navigation group.
     */
    protected static ?int $navigationSort = 8;

    /**
     * The icon used for this resource in the navigation menu.
     */
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    /**
     * The attribute used as the record title (e.g. in breadcrumbs).
     */
    protected static ?string $recordTitleAttribute = 'name';

    /**
     * The label used for this resource in the navigation menu.
     */
    protected static ?string $navigationLabel = 'Outlet';

    /**
     * Configure the table for listing outlet products.
     *
     * @param  Table  $table  The default table instance.
     * @return Table The configured table instance.
     */
    #[Override]
    public static function table(Table $table): Table
    {
        return OutletSkusTable::configure($table);
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
            'index' => ListOutletSkus::route('/'),
        ];
    }
}
