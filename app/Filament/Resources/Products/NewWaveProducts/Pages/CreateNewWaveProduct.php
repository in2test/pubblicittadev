<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\NewWaveProducts\Pages;

use App\Enums\SyncStatus;
use App\Filament\Resources\Products\NewWaveProducts\NewWaveProductResource;
use App\Jobs\SyncNewWaveProductJob;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateNewWaveProduct extends CreateRecord
{
    protected static string $resource = NewWaveProductResource::class;

    /**
     * Ensure the record is created as a NewWave product and is marked pending sync.
     */
    #[Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = Product::TYPE_NEWWAVE;
        $data['sync_status'] = SyncStatus::Pending;

        return $data;
    }

    /**
     * After saving the product, dispatch background sync so the external API can fill details.
     */
    protected function afterCreate(): void
    {
        SyncNewWaveProductJob::dispatch($this->record->id);
    }
}
