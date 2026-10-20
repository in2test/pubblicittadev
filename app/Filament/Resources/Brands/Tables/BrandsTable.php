<?php

declare(strict_types=1);

namespace App\Filament\Resources\Brands\Tables;

use App\Models\Brand;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\ManageRecordsFilter;
use Filament\Tables\Filters\SearchableFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActions\DeleteBulkAction;
use Filament\Tables\Actions\BulkActions\DeleteBulkAction as BulkDeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\Action;

class BrandsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->collection('logos')
                    ->circular()
                    ->width(40),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->toggleable(isToggledHidden: true),
                TextColumn::make('products_count')
                    ->counts('products')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])->icon('heroicon-m-pencil-square'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkDeleteAction::make(),
                ]),
            ]);
    }
}
