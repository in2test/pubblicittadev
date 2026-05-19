<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrintPlacements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PrintPlacementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Descrizione')
                    ->searchable(),
                TextColumn::make('default_price')
                    ->label('Prezzo di Default')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Ordinamento')
                    ->sortable(),
                TextColumn::make('template_path')
                    ->label('Template File')
                    ->state(fn ($record) => $record->template_path ? 'Caricato' : 'Nessuno')
                    ->badge()
                    ->color(fn ($state) => $state === 'Caricato' ? 'success' : 'gray'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order', 'asc');
    }
}
