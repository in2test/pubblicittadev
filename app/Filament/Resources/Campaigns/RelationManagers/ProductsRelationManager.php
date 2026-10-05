<?php

declare(strict_types=1);

namespace App\Filament\Resources\Campaigns\RelationManagers;

use App\Models\Product;
use Filament\Actions\AttachAction as TableAttachAction;
use Filament\Actions\DetachAction as TableDetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Prodotti in Offerta';

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
            ->recordTitleAttribute('name')
            ->query(fn (): Builder => Product::query()->where('offer_price', '>', 0))
            ->columns([
                TextColumn::make('name')
                    ->label('Nome Prodotto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('offer_price')
                    ->label('Prezzo Offerta')
                    ->money('EUR')
                    ->sortable(),
                ToggleColumn::make('is_associated')
                    ->label('Associato')
                    ->getStateUsing(fn ($record) => $record->campaigns()->where('campaign_id', $this->getOwnerRecord()->getKey())->exists())
                    ->updateStateUsing(function ($record, $state): void {
                        $campaign = $this->getOwnerRecord();
                        if ($state) {
                            $campaign->products()->syncWithoutDetaching([$record->getKey()]);
                        } else {
                            $campaign->products()->detach($record->getKey());
                        }
                    }),
            ])
            ->filters([
                CampaignRelationManagerHelper::getAvailabilityFilter(),
            ])
            ->headerActions([
                CampaignRelationManagerHelper::configureAttachAction(
                    TableAttachAction::make(),
                    fn (Builder $query) => $query->where('offer_price', '>', 0)
                ),
            ])
            ->actions([
                TableDetachAction::make(),
            ]);
    }
}
