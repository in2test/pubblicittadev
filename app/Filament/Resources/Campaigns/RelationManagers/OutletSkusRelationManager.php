<?php

declare(strict_types=1);

namespace App\Filament\Resources\Campaigns\RelationManagers;

use App\Models\ProductSku;
use Filament\Actions\AttachAction as TableAttachAction;
use Filament\Actions\DetachAction as TableDetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OutletSkusRelationManager extends RelationManager
{
    protected static string $relationship = 'outletSkus';

    protected static ?string $title = 'Varianti Outlet';

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                // No form needed for attaching existing records
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->query(fn (): Builder => ProductSku::query()
                ->where('is_outlet', true)
                ->whereHas('options.variationType', fn (Builder $q) => $q->where('expose_in_url', true)))
            ->columns([
                TextColumn::make('product.name')
                    ->label('Prodotto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                ToggleColumn::make('is_associated')
                    ->label('Associato')
                    ->getStateUsing(function (ProductSku $record): bool {
                        return $record->campaigns()
                            ->where('campaign_id', $this->getOwnerRecord()->getKey())
                            ->exists();
                    })
                    ->updateStateUsing(function (ProductSku $record, bool $state): void {
                        $campaign = $this->getOwnerRecord();
                        $allOutletSkuIds = ProductSku::where('product_id', $record->product_id)
                            ->where('is_outlet', true)
                            ->pluck('id')
                            ->toArray();

                        if ($state) {
                            $campaign->outletSkus()->syncWithoutDetaching($allOutletSkuIds);
                        } else {
                            $campaign->outletSkus()->detach($allOutletSkuIds);
                        }
                    }),
            ])
            ->filters([
                CampaignRelationManagerHelper::getAvailabilityFilter(),
            ])
            ->headerActions([
                CampaignRelationManagerHelper::configureAttachAction(
                    TableAttachAction::make(),
                    fn (Builder $query) => $query->where('is_outlet', true)
                ),
            ])
            ->actions([
                TableDetachAction::make(),
            ]);
    }
}
