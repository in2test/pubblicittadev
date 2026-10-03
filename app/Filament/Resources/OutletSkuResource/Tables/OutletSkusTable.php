<?php

declare(strict_types=1);

namespace App\Filament\Resources\OutletSkuResource\Tables;

use App\Models\Product;
use App\Models\VariationOption;
use App\Models\VariationType;
use App\Services\ProductOutletService;
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
            ->columns(self::columns())
            ->filters(self::filters())
            ->recordActions([
                self::manageOutletAction(),
            ])
            ->bulkActions([]);
    }

    private static function columns(): array
    {
        return [
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
                ->color(fn (Product $record): string => self::outletStatusColor($record))
                ->state(fn (Product $record): string => self::outletStatus($record)),

            TextColumn::make('min_outlet_price')
                ->label('Prezzo Outlet Attivo')
                ->state(fn (Product $record): string => self::minimumOutletPrice($record)),
        ];
    }

    private static function outletStatusColor(Product $record): string
    {
        $outletSkusCount = $record->skus->where('is_outlet', true)->count();

        if ($outletSkusCount === 0) {
            return 'gray';
        }

        if ($outletSkusCount === $record->skus->count()) {
            return 'success';
        }

        return 'warning';
    }

    private static function outletStatus(Product $record): string
    {
        $total = $record->skus->count();
        $outlet = $record->skus->where('is_outlet', true)->count();

        if ($outlet === 0) {
            return 'Nessun Outlet';
        }

        if ($total > 0 && $outlet === $total) {
            return 'Intero Prodotto ('.$outlet.'/'.$total.')';
        }

        return 'Parziale ('.$outlet.'/'.$total.' varianti)';
    }

    private static function minimumOutletPrice(Product $record): string
    {
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
    }

    private static function filters(): array
    {
        return [
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
        ];
    }

    private static function manageOutletAction(): Action
    {
        return Action::make('manageOutlet')
            ->label('Configura Outlet')
            ->icon('heroicon-m-tag')
            ->color('warning')
            ->modalHeading(fn (Product $record) => 'Gestione Outlet — '.$record->name)
            ->modalWidth('4xl')
            ->fillForm(fn (Product $record): array => self::outletFormState($record))
            ->form(self::outletForm())
            ->action(fn (array $data, Product $record) => self::saveOutletSettings($data, $record));
    }

    private static function outletFormState(Product $record): array
    {
        $skus = $record->skus;
        $totalSkus = $skus->count();
        $outletSkus = $skus->where('is_outlet', true);
        $isEntireProduct = $totalSkus > 0 && $outletSkus->count() === $totalSkus;

        /** @var Collection<int, VariationType> $exposedTypes */
        $exposedTypes = $record->variationTypes->filter(fn ($type) => (bool) $type->expose_in_url);
        if ($exposedTypes->isEmpty()) {
            $exposedTypes = $record->variationTypes->filter(fn ($type) => ! (bool) $type->pivot?->is_modifier);
        }

        $exposedVariants = [];
        if ($exposedTypes->isNotEmpty()) {
            $optionsByType = VariationOption::query()
                ->whereIn('variation_type_id', $exposedTypes->modelKeys())
                ->whereHas('skus', fn ($query) => $query->where('product_id', $record->id))
                ->orderBy('sort_order')
                ->get()
                ->groupBy('variation_type_id');

            foreach ($exposedTypes as $type) {
                foreach ($optionsByType->get($type->id, collect()) as $option) {
                    $optionSkus = $skus->filter(fn ($sku) => $sku->options->contains('id', $option->id));
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
    }

    private static function outletForm(): array
    {
        return [
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
        ];
    }

    private static function saveOutletSettings(array $data, Product $record): void
    {
        $productIsOutlet = (bool) ($data['product_is_outlet'] ?? false);
        $productPrice = isset($data['product_outlet_price']) && $data['product_outlet_price'] !== ''
            ? (float) $data['product_outlet_price']
            : null;

        app(ProductOutletService::class)->updateSkuOutletSettings(
            $record,
            $productIsOutlet,
            $productPrice,
            $data['exposed_variants'] ?? [],
        );

        $record->flushOutletPriceCache();
        $record->updateCachedPrices();

        Notification::make()
            ->title('Outlet aggiornato con successo!')
            ->success()
            ->send();
    }
}
