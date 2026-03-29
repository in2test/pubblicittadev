<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->orderByRaw('COALESCE(parent_id, id) ASC, parent_id IS NOT NULL ASC, name ASC')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->formatStateUsing(fn ($state, Category $record) => $record->parent_id ? "> {$state}" : $state)
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Data di Creazione')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Data di Modifica')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('parent.name')
                    ->label('Categoria di Appartenenza')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('products_count')
                    ->counts('products')
                    ->label('N° Prodotti')
                    ->sortable(),
                TextColumn::make('children_count')
                    ->counts('children')
                    ->label('N° Sottocategorie')
                    ->sortable(),
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
