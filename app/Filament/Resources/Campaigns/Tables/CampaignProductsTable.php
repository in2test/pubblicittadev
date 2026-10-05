<?php

declare(strict_types=1);

namespace App\Filament\Resources\Campaigns\Tables;

use App\Models\Campaign;
use App\Models\Product;
use App\Services\CampaignProductAssociationService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CampaignProductsTable
{
    public static function configure(Table $table, Campaign $campaign): Table
    {
        $service = app(CampaignProductAssociationService::class);

        return $table
            ->recordTitleAttribute('name')
            ->query(self::getEligibleProductsQuery())
            ->columns(self::getColumns($campaign, $service))
            ->filters(self::getFilters($campaign))
            ->recordActions(self::getRecordActions($campaign, $service))
            ->toolbarActions(self::getToolbarActions($campaign, $service));
    }

    /**
     * @return Builder<Product>
     */
    public static function getEligibleProductsQuery(): Builder
    {
        return Product::query()
            ->where(function (Builder $query): void {
                $query->where('offer_price', '>', 0)
                    ->orWhereHas('skus', fn (Builder $q) => $q->where('is_outlet', true));
            })
            ->with([
                'skus' => fn ($q) => $q->where('is_outlet', true)->with('campaigns:id,name'),
                'campaigns:id,name',
                'media',
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    public static function getColumns(Campaign $campaign, CampaignProductAssociationService $service): array
    {
        return [
            ImageColumn::make('image')
                ->label('Foto')
                ->circular()
                ->disk('public')
                ->getStateUsing(fn (Product $record): ?string => $record->getFirstMediaUrl('images', 'thumbnail') ?: null),

            TextColumn::make('name')
                ->label('Prodotto')
                ->searchable()
                ->sortable()
                ->description(fn (Product $record): ?string => $record->sku ? "SKU: {$record->sku}" : null),

            TextColumn::make('price')
                ->label('Prezzo Base')
                ->money('EUR')
                ->sortable(),

            TextColumn::make('promotion_details')
                ->label('Offerta / Outlet')
                ->html()
                ->state(fn (Product $record): string => $service->formatPromotionDetails($record)),

            TextColumn::make('campaign_status')
                ->label('Stato Campagna')
                ->badge()
                ->state(fn (Product $record): string => $service->getStatusLabel($campaign, $record))
                ->color(fn (Product $record): string => match ($service->getStatusLabel($campaign, $record)) {
                    'Associato' => 'success',
                    'Disponibile' => 'gray',
                    default => 'warning',
                }),

            ToggleColumn::make('is_associated')
                ->label('Associato')
                ->getStateUsing(fn (Product $record): bool => $service->isAssociated($campaign, $record))
                ->updateStateUsing(function (Product $record, bool $state) use ($campaign, $service): void {
                    if ($state) {
                        $service->associate($campaign, $record);
                    } else {
                        $service->detach($campaign, $record);
                    }
                }),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function getFilters(Campaign $campaign): array
    {
        $campaignId = $campaign->getKey();

        return [
            SelectFilter::make('association_status')
                ->label('Stato associazione')
                ->options([
                    'associated' => 'Associati a questa campagna',
                    'available' => 'Disponibili (non associati)',
                    'other' => 'In altre campagne',
                ])
                ->query(function (Builder $query, array $data) use ($campaignId): Builder {
                    $value = $data['value'] ?? null;
                    if (! $value) {
                        return $query;
                    }

                    return match ($value) {
                        'associated' => $query->where(function (Builder $q) use ($campaignId): void {
                            $q->whereHas('campaigns', fn (Builder $cq) => $cq->where('campaigns.id', $campaignId))
                                ->orWhereHas('skus', fn (Builder $sq) => $sq->where('is_outlet', true)
                                    ->whereHas('campaigns', fn (Builder $cq) => $cq->where('campaigns.id', $campaignId)));
                        }),
                        'available' => $query->where(function (Builder $q): void {
                            $q->whereDoesntHave('campaigns')
                                ->whereDoesntHave('skus', fn (Builder $sq) => $sq->where('is_outlet', true)->whereHas('campaigns'));
                        }),
                        'other' => $query->where(function (Builder $q) use ($campaignId): void {
                            $q->whereHas('campaigns', fn (Builder $cq) => $cq->where('campaigns.id', '!=', $campaignId))
                                ->orWhereHas('skus', fn (Builder $sq) => $sq->where('is_outlet', true)
                                    ->whereHas('campaigns', fn (Builder $cq) => $cq->where('campaigns.id', '!=', $campaignId)));
                        }),
                        default => $query,
                    };
                }),

            SelectFilter::make('type')
                ->label('Tipo promozione')
                ->options([
                    'offer' => 'Solo Offerta',
                    'outlet' => 'Solo Varianti Outlet',
                    'both' => 'Entrambi (Offerta + Outlet)',
                ])
                ->query(function (Builder $query, array $data): Builder {
                    $value = $data['value'] ?? null;
                    if (! $value) {
                        return $query;
                    }

                    return match ($value) {
                        'offer' => $query->where('offer_price', '>', 0)
                            ->whereDoesntHave('skus', fn (Builder $sq) => $sq->where('is_outlet', true)),
                        'outlet' => $query->where(function (Builder $q): void {
                            $q->whereNull('offer_price')->orWhere('offer_price', '<=', 0);
                        })->whereHas('skus', fn (Builder $sq) => $sq->where('is_outlet', true)),
                        'both' => $query->where('offer_price', '>', 0)
                            ->whereHas('skus', fn (Builder $sq) => $sq->where('is_outlet', true)),
                        default => $query,
                    };
                }),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function getRecordActions(Campaign $campaign, CampaignProductAssociationService $service): array
    {
        return [
            Action::make('associate')
                ->label('Associa')
                ->icon('heroicon-m-plus-circle')
                ->color('success')
                ->visible(fn (Product $record): bool => ! $service->isAssociated($campaign, $record))
                ->action(function (Product $record) use ($campaign, $service): void {
                    $service->associate($campaign, $record);
                    Notification::make()
                        ->title('Prodotto associato alla campagna')
                        ->success()
                        ->send();
                }),

            Action::make('detach')
                ->label('Rimuovi')
                ->icon('heroicon-m-minus-circle')
                ->color('danger')
                ->visible(fn (Product $record): bool => $service->isAssociated($campaign, $record))
                ->action(function (Product $record) use ($campaign, $service): void {
                    $service->detach($campaign, $record);
                    Notification::make()
                        ->title('Prodotto rimosso dalla campagna')
                        ->info()
                        ->send();
                }),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function getToolbarActions(Campaign $campaign, CampaignProductAssociationService $service): array
    {
        return [
            BulkActionGroup::make([
                BulkAction::make('associateSelected')
                    ->label('Associa selezionati')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->action(function (Collection $records) use ($campaign, $service): void {
                        $service->associateMany($campaign, $records);
                        Notification::make()
                            ->title('Prodotti selezionati associati alla campagna')
                            ->success()
                            ->send();
                    }),

                BulkAction::make('detachSelected')
                    ->label('Disassocia selezionati')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->action(function (Collection $records) use ($campaign, $service): void {
                        $service->detachMany($campaign, $records);
                        Notification::make()
                            ->title('Prodotti selezionati rimossi dalla campagna')
                            ->info()
                            ->send();
                    }),
            ]),
        ];
    }
}
