<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\NewWaveProducts\Pages;

use App\Filament\Resources\Products\NewWaveProducts\NewWaveProductResource;
use App\Services\ProductAvailabilityService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditNewWaveProduct extends EditRecord
{
    protected static string $resource = NewWaveProductResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncFromApi')
                ->label('Sincronizza da API')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('success')
                ->action(function (ProductAvailabilityService $service) {
                    $service->syncProduct($this->record);
                    $this->refreshFormData(['name', 'description', 'price']);
                    Notification::make()
                        ->title('Sincronizzazione completata')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
