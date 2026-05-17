<?php

declare(strict_types=1);

namespace App\Filament\Resources\VariationTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariationTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('presentation_type')
                    ->label('Visualizzazione')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'select' => 'Select',
                        'radio' => 'Bottoni',
                        'color_swatch' => 'Color Swatch',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'select' => 'gray',
                        'radio' => 'info',
                        'color_swatch' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('options_count')
                    ->counts('options')
                    ->label('Numero Opzioni')
                    ->badge()
                    ->color('primary'),
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
