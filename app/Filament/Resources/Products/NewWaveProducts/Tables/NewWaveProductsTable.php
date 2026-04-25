<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\NewWaveProducts\Tables;

use App\Enums\SyncStatus;
use App\Filament\Resources\Products\NewWaveProducts\Pages\EditNewWaveProduct;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
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
                            "<div class='flex flex-col gap-1 min-w-[120px]'>
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
                //
            ])
            ->recordUrl(fn (Product $record): string => EditNewWaveProduct::getUrl(['record' => $record]))
            ->recordActions([
                Action::make('view')
                    ->label('Vedi')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Product $record): ?string => $record->category?->slug ? route('product', [
                        'category' => $record->category->slug,
                        'slug' => $record->slug,
                    ]) : null)
                    ->visible(fn (Product $record): bool => (bool) $record->category_id)
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
