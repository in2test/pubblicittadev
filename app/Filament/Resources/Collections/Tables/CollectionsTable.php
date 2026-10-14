<?php

declare(strict_types=1);

namespace App\Filament\Resources\Collections\Tables;

use App\Models\Collection;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CollectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('banner')
                    ->label('Banner / Anteprima')
                    ->state(fn (Collection $record): ?string => $record->getBannerUrl('thumbnail'))
                    ->square(),

                TextColumn::make('name')
                    ->label('Nome Collezione')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('products_count')
                    ->counts('products')
                    ->label('N° Prodotti')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                IconColumn::make('is_featured')
                    ->label('In Evidenza')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Attiva')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Ordine')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Creata il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
