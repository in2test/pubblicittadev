<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\OutletSkuResource\Pages\CreateOutletSku;
use App\Filament\Resources\OutletSkuResource\Pages\EditOutletSku;
use App\Filament\Resources\OutletSkuResource\Pages\ListOutletSkus;
use App\Models\ProductSku;
use App\Models\ProductVariationType;
use App\Models\VariationOption;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OutletSkuResource extends Resource
{
    protected static ?string $model = ProductSku::class;

    protected static UnitEnum|string|null $navigationGroup = 'Catalogo';

    protected static ?string $navigationLabel = 'Gestione Outlet SKU';

    protected static ?string $modelLabel = 'SKU Outlet';

    protected static ?string $pluralModelLabel = 'Gestione Outlet SKU';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-tag';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Identità Variante')
                    ->description('Dati identificativi della variante (non modificabili)')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('product.name')
                                ->label('Prodotto')
                                ->disabled()
                                ->dehydrated(false),
                            TextInput::make('sku')
                                ->label('Codice SKU')
                                ->disabled()
                                ->dehydrated(false),
                            Placeholder::make('variant_options')
                                ->label('Opzioni Variante')
                                ->content(fn (ProductSku $record): string => $record->options->pluck('name')->implode(' / ')),
                        ]),
                    ]),

                Section::make('Impostazioni Outlet')
                    ->description('Configura se l\'articolo è in outlet e definisci il prezzo di override')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('is_outlet')
                                ->label('Articolo Outlet')
                                ->required(),
                            TextInput::make('override_price')
                                ->label('Prezzo di Override (€)')
                                ->numeric()
                                ->prefix('€')
                                ->required(fn (ProductSku $record) => $record->is_outlet),
                        ]),
                    ]),

                Section::make('Magazzino e Disponibilità')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('quantity')
                                ->label('Quantità in Magazzino')
                                ->numeric()
                                ->default(-1),
                            Toggle::make('is_available')
                                ->label('Disponibile')
                                ->default(true),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Prodotto')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('options')
                    ->label('Varianti')
                    ->formatStateUsing(fn ($state, ProductSku $record) => $record->options->pluck('name')->unique()->implode(' / ')),
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
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('setOutlet')
                        ->label('Imposta come Outlet')
                        ->icon('heroicon-m-check-circle')
                        ->action(fn (Builder $query) => $query->update(['is_outlet' => true])),
                    BulkAction::make('removeOutlet')
                        ->label('Rimuovi da Outlet')
                        ->icon('heroicon-m-x-circle')
                        ->action(fn (Builder $query) => $query->update(['is_outlet' => false])),
                    BulkAction::make('bulkOutletByVariation')
                        ->label('Outlet per Variante (Selezionati)')
                        ->icon('heroicon-m-tag')
                        ->color('warning')
                        ->form([
                            CheckboxList::make('variation_options')
                                ->label('Seleziona Colore/Variante per impostare come Outlet')
                                ->options(function (Builder $query) {
                                    // We find the product ID of the first selected record
                                    $firstRecord = $query->first();
                                    if (! $firstRecord) {
                                        return [];
                                    }

                                    /** @var ProductSku */
                                    $productId = (int) $firstRecord->product_id; // @phpstan-ignore-line

                                    // Find exposed variation types for this product
                                    $exposedTypeIds = ProductVariationType::where('product_id', $productId)
                                        ->where('is_modifier', false)
                                        ->pluck('variation_type_id');

                                    return VariationOption::whereIn('variation_type_id', $exposedTypeIds)
                                        ->whereHas('skus', function (Builder $q) {
                                            /** @var VariationOption $option */
                                            $option = $q->getModel();
                                            $q->where('product_sku_options.variation_option_id', $option->id);
                                        })
                                        ->pluck('name', 'id');
                                })
                                ->required()
                                ->searchable(),
                        ])
                        ->action(function (array $data, Builder $query): void {
                            $optionIds = $data['variation_options'];
                            $firstRecord = $query->first();
                            if (! $firstRecord) {
                                return;
                            }

                            ProductSku::where('product_id', (int) $firstRecord->product->id) // @phpstan-ignore-line
                                ->whereHas('options', function (Builder $q) use ($optionIds) {
                                    $q->whereIn('variation_option_id', $optionIds);
                                })
                                ->update(['is_outlet' => true]);
                        })
                        ->successNotificationTitle('Varianti impostate come Outlet'),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['product', 'options']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOutletSkus::route('/'),
            'create' => CreateOutletSku::route('/create'),
            'edit' => EditOutletSku::route('/{record}/edit'),
        ];
    }
}
