<?php

declare(strict_types=1);

namespace App\Filament\Resources\Campaigns;

use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Filament\Resources\Campaigns\Pages\EditCampaign;
use App\Filament\Resources\Campaigns\Pages\ListCampaigns;
use App\Filament\Resources\Campaigns\RelationManagers\OutletSkusRelationManager;
use App\Filament\Resources\Campaigns\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\Campaigns\Schemas\CampaignForm;
use App\Filament\Resources\Campaigns\Tables\CampaignsTable;
use App\Models\Campaign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string|UnitEnum|null $navigationGroup = 'Promozioni';

    protected static ?string $navigationLabel = 'Campagne';

    protected static ?string $modelLabel = 'Campagna';

    protected static ?string $pluralModelLabel = 'Campagne';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return CampaignForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return CampaignsTable::configure($table);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            ProductsRelationManager::class,
            OutletSkusRelationManager::class,
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListCampaigns::route('/'),
            'create' => CreateCampaign::route('/create'),
            'edit' => EditCampaign::route('/{record}/edit'),
        ];
    }
}
