<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\NewWaveProducts\Pages;

use App\Filament\Resources\Products\NewWaveProducts\NewWaveProductResource;
use App\Jobs\SyncNewWaveProductJob;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditNewWaveProduct extends EditRecord
{
    protected static string $resource = NewWaveProductResource::class;

    /**
     * Provide a simple header action to re-sync product data from the external NWG API.
     */
    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncFromApi')
                ->label('Sincronizza da API')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('success')
                ->action(function () {
                    $this->dispatchSyncJob();
                }),
            DeleteAction::make(),
        ];
    }

    /**
     * Dispatch the background job and notify the user.
     */
    protected function dispatchSyncJob(): void
    {
        SyncNewWaveProductJob::dispatch($this->record->id);

        Notification::make()
            ->title('Sincronizzazione in coda')
            ->body('La sincronizzazione avverrà in background. Ricarica la pagina per vedere i risultati.')
            ->success()
            ->send();
    }
}
