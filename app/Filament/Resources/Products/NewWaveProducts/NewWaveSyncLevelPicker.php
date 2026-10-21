<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\NewWaveProducts;

use App\Enums\SyncLevel;
use App\Jobs\SyncNewWaveProductJob;
use App\Models\Product;
use Filament\Forms\Components\Radio;
use Illuminate\Database\Eloquent\Collection;

/**
 * Shared build of the NewWave sync level picker.
 *
 * Both the edit page action and the table bulk action offer the same three
 * levels, so the form and the dispatch live in one place.
 */
class NewWaveSyncLevelPicker
{
    /**
     * @return array<int, Radio>
     */
    public static function make(): array
    {
        return [
            Radio::make('level')
                ->label('Livello di sincronizzazione')
                ->options(collect(SyncLevel::cases())
                    ->mapWithKeys(fn (SyncLevel $level): array => [$level->value => $level->getLabel()])
                    ->all())
                ->descriptions(collect(SyncLevel::cases())
                    ->mapWithKeys(fn (SyncLevel $level): array => [$level->value => $level->getDescription()])
                    ->all())
                ->default(SyncLevel::Full->value)
                ->required(),
        ];
    }

    /**
     * Dispatch a sync job for every given product at the requested level.
     *
     * @param  Collection<int, Product>|array<int, Product>  $products
     */
    public static function dispatch(Collection|array $products, SyncLevel $level): void
    {
        foreach ($products as $product) {
            SyncNewWaveProductJob::dispatch($product->getKey(), $level);
        }
    }
}
