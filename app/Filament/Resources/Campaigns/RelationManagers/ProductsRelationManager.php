<?php

declare(strict_types=1);

namespace App\Filament\Resources\Campaigns\RelationManagers;

use App\Filament\Resources\Campaigns\Tables\CampaignProductsTable;
use App\Models\Campaign;
use App\Models\Product;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Prodotti e Outlet';

    public function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        /** @var Campaign $campaign */
        $campaign = $this->getOwnerRecord();

        return CampaignProductsTable::configure($table, $campaign);
    }

    protected function resolveTableRecord(?string $key): ?Product
    {
        if ($key === null) {
            return null;
        }

        return Product::find($key);
    }

    public function getTableRecordKey(mixed $record): string
    {
        if ($record instanceof Product) {
            return (string) $record->getKey();
        }

        return (string) ($record['id'] ?? '');
    }
}
