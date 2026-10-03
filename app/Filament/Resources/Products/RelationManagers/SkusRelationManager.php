<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\Product;
use App\Models\ProductSku;
use App\Models\VariationOption;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SkusRelationManager extends RelationManager
{
    protected static string $relationship = 'skus';

    protected static ?string $title = 'Gestione SKU e Outlet';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('sku')
                    ->required(),
                Toggle::make('is_outlet')
                    ->label('Articolo Outlet'),
                TextInput::make('override_price')
                    ->numeric()
                    ->prefix('€'),
                TextInput::make('quantity')
                    ->numeric()
                    ->default(-1),
                Toggle::make('is_available')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('options'))
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('options')
                    ->label('Varianti')
                    ->formatStateUsing(fn ($state, ProductSku $record) => $record->options->pluck('name')->implode(' / ')),
                ToggleColumn::make('is_outlet')
                    ->label('Outlet')
                    ->alignCenter(),
                TextInputColumn::make('override_price')
                    ->label('Prezzo Override (€)')
                    ->prefix('€'),
                TextColumn::make('quantity')
                    ->label('Quantità')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_outlet')
                    ->label('Stato Outlet'),
            ])
            ->headerActions([
                Action::make('bulkOutletByVariation')
                    ->label('Outlet per Variante')
                    ->icon('heroicon-m-tag')
                    ->color('warning')
                    ->form([
                        CheckboxList::make('variation_options')
                            ->label('Seleziona Colore/Variante per impostare come Outlet')
                            ->options(function (Product $owner) {
                                $exposedTypeIds = $owner->productVariationTypes()
                                    ->where('is_modifier', false)
                                    ->pluck('variation_type_id');

                                return VariationOption::whereIn('variation_type_id', $exposedTypeIds)
                                    ->whereHas('skus.product', function (Builder $query) use ($owner) {
                                        $query->where('product_id', $owner->id);
                                    })
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (array $data, Product $owner): void {
                        $optionIds = $data['variation_options'];

                        ProductSku::where('product_id', $owner->id)
                            ->whereHas('options', function (Builder $query) use ($optionIds) {
                                $query->whereIn('variation_option_id', $optionIds);
                            })
                            ->update(['is_outlet' => true]);
                    })
                    ->successNotificationTitle('Varianti impostate come Outlet'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('setOutlet')
                    ->label('Imposta come Outlet')
                    ->icon('heroicon-m-check-circle')
                    ->action(fn (Builder $query) => $query->update(['is_outlet' => true])),
                BulkAction::make('removeOutlet')
                    ->label('Rimuovi da Outlet')
                    ->icon('heroicon-m-x-circle')
                    ->action(fn (Builder $query) => $query->update(['is_outlet' => false])),
            ]);
    }
}
