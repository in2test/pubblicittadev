<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Tables;

use App\Models\Category;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('Immagine')
                    ->state(fn (Product $record) => $record->getFirstImage()->thumbnail_url ?? $record->getThumbnailUrl())
                    ->circular()
                    ->size(50),

                TextColumn::make('name')
                    ->label('Nome Prodotto')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Product $record): string => $record->sku ?? 'Nessun SKU'),

                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('pricing_model')
                    ->label('Modello Vendita')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'fixed' => 'gray',
                        'quantity' => 'success',
                        'area' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'fixed' => 'Fisso',
                        'quantity' => 'Scaglioni',
                        'area' => 'Metratura',
                        default => ucfirst($state),
                    }),

                TextColumn::make('price')
                    ->label('Prezzo Base')
                    ->money('EUR')
                    ->sortable()
                    ->alignment('right'),

                TextColumn::make('is_active')
                    ->label('Stato')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Attivo' : 'Inattivo'),

                TextColumn::make('updated_at')
                    ->label('Ultima Modifica')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload(),
                SelectFilter::make('pricing_model')
                    ->label('Modello Vendita')
                    ->options([
                        'fixed' => 'Prezzo Fisso',
                        'quantity' => 'A Scaglioni',
                        'area' => 'A Metratura',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Stato Prodotto')
                    ->placeholder('Tutti')
                    ->trueLabel('Solo Attivi')
                    ->falseLabel('Solo Inattivi'),
            ], layout: FiltersLayout::AboveContent)
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

                    DeleteBulkAction::make(),
                ]),
            ])
            ->filtersFormColumns(3);
    }
}
