<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShippingTiers;

use App\Filament\Resources\ShippingTiers\Pages\CreateShippingTier;
use App\Filament\Resources\ShippingTiers\Pages\EditShippingTier;
use App\Filament\Resources\ShippingTiers\Pages\ListShippingTiers;
use App\Models\ShippingTier;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

class ShippingTierResource extends Resource
{
    protected static ?string $model = ShippingTier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Nome (Es: Standard, Gratuita)')
                    ->maxLength(255),
                TextInput::make('min_order_total')
                    ->label('Subtotale Minimo Ordine (€)')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                TextInput::make('shipping_cost')
                    ->label('Costo di Spedizione (€)')
                    ->required()
                    ->numeric()
                    ->default(0.00),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('min_order_total')
                    ->label('Subtotale Minimo (€)')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('shipping_cost')
                    ->label('Costo di Spedizione (€)')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('min_order_total', 'asc')
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListShippingTiers::route('/'),
            'create' => CreateShippingTier::route('/create'),
            'edit' => EditShippingTier::route('/{record}/edit'),
        ];
    }
}
