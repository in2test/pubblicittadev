<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Tables;

use App\Jobs\SyncNewWaveProductJob;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->searchable()
                    ->tooltip(function (Product $record): ?HtmlString {
                        $url = $record->getFirstImage()->thumbnail_url ?? $record->getThumbnailUrl();
                        if (! $url) {
                            return null;
                        }

                        return new HtmlString(
                            "<img src='$url' style='width:150px;height:150px;object-fit:cover;border-radius:8px;' />"
                        );
                    }),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('price')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('category.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload(),
            ],
                layout: FiltersLayout::BelowContent)
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
                            'product' => (string) $record->getAttribute('slug'),
                        ]);
                    })
                    ->visible(fn (Product $record): bool => (bool) $record->category_id)
                    ->openUrlInNewTab(),
                EditAction::make(),
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
            ])
            ->filtersFormColumns(3);
    }
}
