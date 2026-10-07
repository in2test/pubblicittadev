<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Schemas\ProductVariationFields;
use App\Models\VariationOption;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

final class ProductResourceSkus
{
    /**
     * Returns the repeater for SKUs and specific variation pricing tiers.
     *
     * This section generates base SKU variations from the main product variations
     * (the ones marked as 'redefine'). It includes quantity discount tiers for
     * each SKU generated.
     */
    public static function make(): Repeater
    {
        return Repeater::make('skus')
            ->relationship('skus')
            ->label('Prezzi e SKU Varianti')
            ->defaultItems(0)
            ->schema([
                TextInput::make('sku')
                    ->label('Codice SKU Variante')
                    ->required(),
                TextInput::make('override_price')
                    ->label('Prezzo di Override (€)')
                    ->numeric()
                    ->prefix('€'),
                Toggle::make('is_outlet')
                    ->label('Articolo Outlet')
                    ->default(false)
                    ->live(),
                TextInput::make('quantity')
                    ->label('Disponibilità Magazzino')
                    ->numeric()
                    ->default(-1)
                    ->nullable(),
                Toggle::make('is_available')
                    ->label('Disponibile')
                    ->default(true),
                Select::make('options')
                    ->label('Opzioni Associate')
                    ->relationship('options', 'name')
                    ->multiple()
                    ->preload()
                    // Filter available options based on those mapped in the base variations repeater
                    // that use the 'redefine' impact type (i.e., generate a new sku/price).
                    ->options(function (Get $get) {
                        $allVariations = $get('../../productVariationTypes') ?? [];
                        $optionIds = [];
                        foreach ($allVariations as $varType) {
                            $isModifier = $varType['is_modifier'] ?? false;
                            $impactType = $varType['impact_type'] ?? null;
                            if ($impactType === 'redefine' || (! $isModifier && $impactType === null)) {
                                $options = $varType['options'] ?? [];
                                foreach ($options as $opt) {
                                    if (! empty($opt['variation_option_id'])) {
                                        $optionIds[] = $opt['variation_option_id'];
                                    }
                                }
                            }
                        }
                        if ($optionIds === []) {
                            return VariationOption::pluck('name', 'id');
                        }

                        return VariationOption::whereIn('id', $optionIds)->pluck('name', 'id');
                    })
                    ->required()
                    ->live()
                    // Auto-generate the variant SKU suffix when an option is selected.
                    ->afterStateUpdated(function (Get $get, Set $set, ?array $state) {
                        $baseSku = $get('../../sku') ?? '';
                        if ($state === null || $state === []) {
                            $set('sku', $baseSku);

                            return;
                        }

                        // Order the options so the SKU is consistently generated.
                        $options = VariationOption::whereIn('id', $state)->orderBy('sort_order')->get();
                        $suffix = $options->map(function (VariationOption $option) {
                            $val = $option->value ?? $option->name;

                            return Str::slug($val);
                        })->implode('-');

                        $finalSku = $baseSku ? "{$baseSku}-".Str::upper($suffix) : Str::upper($suffix);
                        $set('sku', $finalSku);
                    }),
                // Inject the pricing tiers (quantity discounts) for this specific SKU variation
                ProductVariationFields::getPricingTiersRepeater()
                    ->label('Sconti per Quantità (Scaglioni)')
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->columnSpanFull()
            ->addActionLabel('Aggiungi SKU Variante');
    }
}
