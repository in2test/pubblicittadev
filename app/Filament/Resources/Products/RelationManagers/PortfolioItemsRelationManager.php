<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PortfolioItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'portfolioItems';

    protected static ?string $title = 'Portfolio / Lavori realizzati';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->label('In evidenza')
                    ->boolean(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->recordTitleAttribute('title')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['title']),
            ])
            ->actions([
                DetachAction::make(),
            ])
            ->bulkActions([
                DetachBulkAction::make(),
            ]);
    }
}
