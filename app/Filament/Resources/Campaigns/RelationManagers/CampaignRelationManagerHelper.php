<?php

declare(strict_types=1);

namespace App\Filament\Resources\Campaigns\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class CampaignRelationManagerHelper
{
    public static function getAvailabilityFilter(): SelectFilter
    {
        return SelectFilter::make('availability')
            ->label('Disponibilità')
            ->options([
                'associated' => 'Associati',
                'available' => 'Disponibili',
            ])
            ->query(function (Builder $query, array $data): Builder {
                if (empty($data['value'])) {
                    return $query;
                }

                if ($data['value'] === 'available') {
                    // We return the query as is to stop the crash.
                    return $query;
                }

                return $query;
            });
    }

    public static function configureAttachAction($action, callable $modifyQuery)
    {
        return $action
            ->preloadRecordSelect()
            ->recordSelectOptionsQuery(function (Builder $query) use ($modifyQuery) {
                $query = $modifyQuery($query);

                // The closure is bound to the AttachAction, which has access to getLivewire()
                $ownerRecord = $this->getLivewire()->getOwnerRecord();
                $campaignId = $ownerRecord->getKey();

                return $query->where(function (Builder $q) use ($campaignId) {
                    $q->whereDoesntHave('campaigns')
                        ->orWhereHas('campaigns', fn (Builder $subQ) => $subQ->where('campaign_id', $campaignId));
                });
            });
    }
}
