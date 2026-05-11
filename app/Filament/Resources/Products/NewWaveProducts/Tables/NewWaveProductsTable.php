<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\NewWaveProducts\Tables;

use App\Enums\SyncStatus;
use App\Filament\Resources\Products\NewWaveProducts\Pages\EditNewWaveProduct;
use App\Jobs\SyncNewWaveProductJob;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class NewWaveProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('2s')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('category'))
            ->columns([
                TextColumn::make('sku')
                    ->searchable()
                    ->tooltip(function (Product $record): ?HtmlString {
                        $url = $record->getFirstImage()->thumb ?? $record->getThumbnailUrl();
                        if (! $url) {
                            return null;
                        }

                        return new HtmlString(
                            "<img src='$url' style='width:150px;height:150px;object-fit:cover;border-radius:8px;' />"
                        );
                    }),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('price')
                    ->label('Prezzo')
                    ->formatStateUsing(fn ($state) => '€'.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('sync_status')
                    ->label('Sync')
                    ->badge()
                    ->sortable(),
                TextColumn::make('sync_progress')
                    ->label('Progresso')
                    ->formatStateUsing(function ($state, $record): string|HtmlString {
                        if ($record->sync_status !== SyncStatus::Syncing) {
                            return '';
                        }

                        return new HtmlString(
                            "<div class='flex flex-col gap-1 min-w-30'>
                                <span class='text-xs text-gray-500 font-medium'>{$state}%</span>
                                <div class='w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700'>
                                    <div class='bg-primary-600 h-2 rounded-full transition-all duration-500' style='width: {$state}%'></div>
                                </div>
                             </div>"
                        );
                    }),
                TextColumn::make('synced_at')
                    ->label('Ultimo Sync')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Attivo')
                    ->disabled(fn ($record) => $record->sync_status !== SyncStatus::Synced)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Categoria'),
            ])
            ->recordUrl(fn (Product $record): string => EditNewWaveProduct::getUrl(['record' => $record]))
            ->recordActions([
                Action::make('view')
                    ->label('Vedi')
                    ->icon('heroicon-o-eye')
                    ->url(function (Product $record): ?string {
                        /** @var Category|null $category */
                        $category = $record->category;
                        if (! $category?->slug) {
                            return null;
                        }

                        return route('product', [
                            'category' => (string) $category->slug,
                            'slug' => (string) $record->getAttribute('slug'),
                        ]);
                    })
                    ->visible(fn (Product $record): bool => (bool) $record->category_id)
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Attiva')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each(fn (Product $record) => $record->update(['is_active' => true])))
                        ->requiresConfirmation()
                        ->modalHeading('Attiva prodotti')
                        ->modalDescription('Sei sicuro di voler attivare i prodotti selezionati?')
                        ->modalButton('Attiva'),
                    BulkAction::make('deactivate')
                        ->label('Disattiva')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (Collection $records) => $records->each(fn (Product $record) => $record->update(['is_active' => false])))
                        ->requiresConfirmation()
                        ->modalHeading('Disattiva prodotti')
                        ->modalDescription('Sei sicuro di voler disattivare i prodotti selezionati?')
                        ->modalButton('Disattiva'),
                    BulkAction::make('synchronize')
                        ->label('Sincronizza')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->action(fn (Collection $records) => $records->each(fn (Product $record) => SyncNewWaveProductJob::dispatch($record)))
                        ->requiresConfirmation()
                        ->modalHeading('Sincronizza prodotti')
                        ->modalDescription('Sei sicuro di voler sincronizzare i prodotti selezionati?')
                        ->modalButton('Sincronizza'),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
