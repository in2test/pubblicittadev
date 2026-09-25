<?php

declare(strict_types=1);

namespace App\Filament\Resources\OutletSkuResource\Tables;

use App\Models\Product;
use App\Models\ProductSku;
use App\Models\VariationOption;
use App\Models\VariationType;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OutletSkusTable
{
    /**
     * Configure the table for listing outlet products.
     *
     * @param  Table  $table  The default table instance.
     * @return Table The configured table instance.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'category',
                'skus.options.variationType',
                'variationTypes',
            ]))
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('Immagine')
                    ->state(fn (Product $record) => $record->getFirstImage()->thumbnail_url ?? $record->getThumbnailUrl())
                    ->circular()
                    ->size(45),

                TextColumn::make('name')
                    ->label('Prodotto')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Product $record): string => $record->sku ?? ''),

                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('price')
                    ->label('Prezzo Base')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('outlet_summary')
                    ->label('Stato Outlet')
                    ->badge()
                    ->color(function (Product $record): string {
                        $outletSkusCount = $record->skus->where('is_outlet', true)->count();
                        if ($outletSkusCount === 0) {
                            return 'gray';
                        }
                        if ($outletSkusCount === $record->skus->count()) {
                            return 'success';
                        }

                        return 'warning';
                    })
                    ->state(function (Product $record): string {
                        $total = $record->skus->count();
                        $outlet = $record->skus->where('is_outlet', true)->count();

                        if ($outlet === 0) {
                            return 'Nessun Outlet';
                        }
                        if ($total > 0 && $outlet === $total) {
                            return 'Intero Prodotto ('.$outlet.'/'.$total.')';
                        }

                        return 'Parziale ('.$outlet.'/'.$total.' varianti)';
                    }),

                TextColumn::make('min_outlet_price')
                    ->label('Prezzo Outlet Attivo')
                    ->state(function (Product $record): string {
                        $outletSkus = $record->skus->where('is_outlet', true);
                        if ($outletSkus->isEmpty()) {
                            return '—';
                        }

                        $minPrice = $outletSkus->whereNotNull('override_price')->min('override_price');
                        $maxPrice = $outletSkus->whereNotNull('override_price')->max('override_price');

                        if ($minPrice === null) {
                            return 'Attivo (senza override)';
                        }

                        if ($minPrice === $maxPrice) {
                            return '€'.number_format((float) $minPrice, 2, ',', '.');
                        }

                        return 'Da €'.number_format((float) $minPrice, 2, ',', '.').' a €'.number_format((float) $maxPrice, 2, ',', '.');
                    }),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_outlet')
                    ->label('Articoli in Outlet')
                    ->placeholder('Tutti')
                    ->trueLabel('Solo Prodotti con Outlet')
                    ->falseLabel('Senza Outlet')
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === true) {
                            $query->whereHas('skus', fn ($q) => $q->where('is_outlet', true));
                        } elseif ($data['value'] === false) {
                            $query->whereDoesntHave('skus', fn ($q) => $q->where('is_outlet', true));
                        }
                    }),
            ])
            ->recordActions([
                Action::make('manageOutlet')
                    ->label('Configura Outlet')
                    ->icon('heroicon-m-tag')
                    ->color('warning')
                    ->modalHeading(fn (Product $record) => 'Gestione Outlet — '.$record->name)
                    ->modalWidth('4xl')
                    ->fillForm(function (Product $record): array {
                        $skus = $record->skus;
                        $totalSkus = $skus->count();
                        $outletSkus = $skus->where('is_outlet', true);
                        $isEntireProduct = $totalSkus > 0 && $outletSkus->count() === $totalSkus;

                        /** @var Collection<int, VariationType> $exposedTypes */
                        $exposedTypes = $record->variationTypes->filter(fn ($t) => (bool) $t->expose_in_url);
                        if ($exposedTypes->isEmpty()) {
                            $exposedTypes = $record->variationTypes->filter(fn ($t) => ! (bool) $t->pivot?->is_modifier);
                        }

                        $exposedVariants = [];
                        if ($exposedTypes->isNotEmpty()) {
                            foreach ($exposedTypes as $type) {
                                $options = VariationOption::where('variation_type_id', $type->id)
                                    ->whereHas('skus', fn ($q) => $q->where('product_id', $record->id))
                                    ->orderBy('sort_order')
                                    ->get();

                                foreach ($options as $option) {
                                    $optionSkus = $skus->filter(fn ($s) => $s->options->contains('id', $option->id));
                                    $optionOutletCount = $optionSkus->where('is_outlet', true)->count();
                                    $isOptionOutlet = $optionSkus->isNotEmpty() && $optionOutletCount === $optionSkus->count();
                                    $firstOverride = $optionSkus->firstWhere('override_price', '!==', null)?->override_price;

                                    $exposedVariants[] = [
                                        'option_id' => $option->id,
                                        'type_name' => $type->name,
                                        'option_name' => $option->name,
                                        'color_hex' => $option->color_hex,
                                        'is_outlet' => $isOptionOutlet,
                                        'outlet_price' => $firstOverride,
                                        'skus_count' => $optionSkus->count(),
                                    ];
                                }
                            }
                        }

                        $commonPrice = $outletSkus->firstWhere('override_price', '!==', null)?->override_price;

                        return [
                            'product_is_outlet' => $isEntireProduct,
                            'product_outlet_price' => $isEntireProduct ? $commonPrice : null,
                            'apply_to_all' => false,
                            'exposed_variants' => $exposedVariants,
                        ];
                    })
                    ->form([
                        Section::make('Outlet su Intero Prodotto')
                            ->description('Attivando questa opzione o impostando un prezzo qui, tutte le varianti SKU del prodotto verranno aggiornate.')
                            ->schema([
                                Grid::make(2)->schema([
                                    Checkbox::make('product_is_outlet')
                                        ->label('Imposta l\'intero prodotto come Outlet')
                                        ->helperText('Se selezionato, tutti gli SKU attuali diventano Outlet.'),

                                    TextInput::make('product_outlet_price')
                                        ->label('Prezzo Outlet Prodotto (€)')
                                        ->numeric()
                                        ->prefix('€')
                                        ->placeholder('es. 5.90')
                                        ->helperText('Prezzo valido per tutte le varianti se l\'intero prodotto è in Outlet.'),
                                ]),
                            ]),

                        Section::make('Outlet per Singola Variante Esposta')
                            ->description('Configura l\'outlet e il prezzo specifico per ciascuna variante esposta (es. Colore Bianco, Giallo, ecc.). Tutti gli SKU sottostanti (taglie S, M, L...) erediteranno questi valori.')
                            ->schema([
                                Repeater::make('exposed_variants')
                                    ->label('Varianti Esposte')
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false)
                                    ->schema([
                                        Grid::make(12)->schema([
                                            Placeholder::make('variant_display')
                                                ->label('Variante')
                                                ->content(fn (Get $get): string => ($get('type_name') ?? '').': '.($get('option_name') ?? ''))
                                                ->columnSpan(5),

                                            Checkbox::make('is_outlet')
                                                ->label('In Outlet')
                                                ->columnSpan(3),

                                            TextInput::make('outlet_price')
                                                ->label('Prezzo Override (€)')
                                                ->numeric()
                                                ->prefix('€')
                                                ->placeholder('es. 4.50')
                                                ->columnSpan(4),
                                        ]),
                                    ]),
                            ]),
                    ])
                    ->action(function (array $data, Product $record): void {
                        $productIsOutlet = (bool) ($data['product_is_outlet'] ?? false);
                        $productPrice = isset($data['product_outlet_price']) && $data['product_outlet_price'] !== ''
                            ? (float) $data['product_outlet_price']
                            : null;

                        $exposedVariants = $data['exposed_variants'] ?? [];

                        if ($productIsOutlet) {
                            $updateData = ['is_outlet' => true];
                            if ($productPrice !== null) {
                                $updateData['override_price'] = $productPrice;
                            }
                            $record->skus()->update($updateData);

                            foreach ($exposedVariants as $variant) {
                                if (isset($variant['option_id']) && ! empty($variant['outlet_price'])) {
                                    $optionId = (int) $variant['option_id'];
                                    $customPrice = (float) $variant['outlet_price'];

                                    ProductSku::where('product_id', $record->id)
                                        ->whereHas('options', fn ($q) => $q->where('variation_option_id', $optionId))
                                        ->update([
                                            'is_outlet' => (bool) ($variant['is_outlet'] ?? true),
                                            'override_price' => $customPrice,
                                        ]);
                                }
                            }
                        } else {
                            if (! empty($exposedVariants)) {
                                foreach ($exposedVariants as $variant) {
                                    $optionId = (int) ($variant['option_id'] ?? 0);
                                    if ($optionId <= 0) {
                                        continue;
                                    }

                                    $isOutlet = (bool) ($variant['is_outlet'] ?? false);
                                    $price = isset($variant['outlet_price']) && $variant['outlet_price'] !== ''
                                        ? (float) $variant['outlet_price']
                                        : null;

                                    $updateData = ['is_outlet' => $isOutlet];

                                    if ($price !== null) {
                                        $updateData['override_price'] = $price;
                                    } elseif (! $isOutlet) {
                                        $updateData['override_price'] = null;
                                    }

                                    ProductSku::where('product_id', $record->id)
                                        ->whereHas('options', fn ($q) => $q->where('variation_option_id', $optionId))
                                        ->update($updateData);
                                }
                            } else {
                                // Simple product without exposed variants — reset all SKUs
                                $record->skus()->update([
                                    'is_outlet' => false,
                                    'override_price' => null,
                                ]);
                            }
                        }

                        $record->flushOutletPriceCache();
                        $record->updateCachedPrices();

                        Notification::make()
                            ->title('Outlet aggiornato con successo!')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }
}
