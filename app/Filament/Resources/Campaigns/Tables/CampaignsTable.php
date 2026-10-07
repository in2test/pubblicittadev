<?php

declare(strict_types=1);

namespace App\Filament\Resources\Campaigns\Tables;

use App\Models\Campaign;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Campagna')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->state(fn (Campaign $record): string => $record->statusLabel())
                    ->color(fn (Campaign $record): string => match ($record->statusLabel()) {
                        'Attiva' => 'success',
                        'Programmata' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('starts_at')
                    ->label('Inizio')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Immediata')
                    ->timezone('Europe/Rome')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Fine')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Indefinita')
                    ->timezone('Europe/Rome')
                    ->sortable(),
                TextColumn::make('now')
                    ->label('Ora')
                    ->getStateUsing(fn (): string => now('Europe/Rome')->format('d/m/Y H:i')),
                TextColumn::make('products_count')
                    ->label('Offerte')
                    ->counts('products'),
                TextColumn::make('outlet_skus_count')
                    ->label('Varianti outlet')
                    ->counts('outletSkus'),
            ])
            ->filters([
                Filter::make('active')
                    ->label('Solo campagne attive')
                    ->query(fn (Builder $query): Builder => $query->active()),
            ])
            ->defaultSort('starts_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
