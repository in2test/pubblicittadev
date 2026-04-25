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

    #[Override]
    public function mount(int|string $record): void
    {
        ini_set('memory_limit', '4G');
        parent::mount($record);
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncFromApi')
                ->label('Sincronizza da API')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('success')
                ->action(function () {
                    SyncNewWaveProductJob::dispatch($this->record);

                    Notification::make()
                        ->title('Sincronizzazione in coda')
                        ->body('La sincronizzazione avverrà in background. Ricarica la pagina per vedere i risultati.')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
