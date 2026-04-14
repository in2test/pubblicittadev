<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductVariations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductVariationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Prodotto')
                    ->searchable(),
                TextColumn::make('color.color_name')
                    ->label('Colore')
                    ->searchable(),
                TextColumn::make('size.size')
                    ->label('Taglia')
                    ->searchable(),
                TextColumn::make('printPlacement.name')
                    ->label('Posizione Stampa')
                    ->searchable(),
                TextColumn::make('printSide.name')
                    ->label('Lato Stampa')
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('Giacenza')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_available')
                    ->label('Disponibile')
                    ->boolean(),
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
                //
            ])
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
