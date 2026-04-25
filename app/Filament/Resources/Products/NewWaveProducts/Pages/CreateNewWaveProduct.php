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

    #[Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = Product::TYPE_NEWWAVE;
        $data['sync_status'] = SyncStatus::Pending;

        return $data;
    }

    protected function afterCreate(): void
    {
        SyncNewWaveProductJob::dispatch($this->record);
    }
}
