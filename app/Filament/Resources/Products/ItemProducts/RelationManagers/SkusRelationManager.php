<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\ItemProducts\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

class SkusRelationManager extends RelationManager
{
    protected static string $relationship = 'skus';

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sku')
                    ->label('Codice SKU Variante')
                    ->required()
                    ->maxLength(255),
                TextInput::make('quantity')
                    ->label('Quantità in Magazzino')
                    ->numeric()
                    ->required()
                    ->default(100),
                Toggle::make('is_available')
                    ->label('Disponibile')
                    ->default(true),
                TextInput::make('override_price')
                    ->label('Prezzo Fisso per Variante (€)')
                    ->numeric()
                    ->prefix('€'),

                Repeater::make('pricingTiers')
                    ->relationship('pricingTiers')
                    ->label('Prezzi a Scaglioni per questa Variante')
                    ->schema([
                        TextInput::make('min_quantity')
                            ->label('Quantità Minima')
                            ->numeric()
                            ->required()
                            ->default(1),
                        TextInput::make('price_per_unit')
                            ->label('Prezzo Unitario (€)')
                            ->numeric()
                            ->required()
                            ->prefix('€'),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->addActionLabel('Aggiungi Scaglione di Prezzo'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('options.name')
                    ->label('Varianti')
                    ->badge()
                    ->separator(','),
                TextColumn::make('quantity')
                    ->label('Giacenza')
                    ->numeric(),
                IconColumn::make('is_available')
                    ->label('Disponibile')
                    ->boolean(),
                TextColumn::make('pricing_tiers_count')
                    ->counts('pricingTiers')
                    ->label('N. Scaglioni'),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Gestisci Prezzi / Modifica'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
