<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\NewWaveProducts\Pages;

use App\Enums\SyncLevel;
use App\Filament\Resources\Products\NewWaveProducts\NewWaveProductResource;
use App\Filament\Resources\Products\NewWaveProducts\NewWaveSyncLevelPicker;
use App\Models\Product;
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
                ->modalHeading('Sincronizza da API')
                ->modalDescription('Scegli il livello di sincronizzazione da eseguire.')
                ->modalSubmitActionLabel('Sincronizza')
                ->schema(NewWaveSyncLevelPicker::make())
                ->action(function (array $data): void {
                    /** @var Product $record */
                    $record = $this->record;
                    $level = SyncLevel::tryFrom((string) ($data['level'] ?? '')) ?? SyncLevel::Full;

                    NewWaveSyncLevelPicker::dispatch([$record], $level);

                    Notification::make()
                        ->title('Sincronizzazione in coda')
                        ->body("La sincronizzazione ({$level->getLabel()}) avverrà in background. Ricarica la pagina per vedere i risultati.")
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
