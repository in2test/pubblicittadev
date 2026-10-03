<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ModifierType;
use App\Models\VariationOption;
use App\Models\VariationType;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

final class ProductVariationFields
{
    /**
     * BASE VARIATIONS repeater (is_modifier = false).
     *
     * These define the price structure of the product (e.g. Thickness → 5mm, 10mm).
     * Each variation group can carry its own gallery images via the ProductVariationType
     * Spatie Media collection "option_images".
     */
    public static function getBaseVariationsRepeater(): Repeater
    {
        return Repeater::make('baseVariationTypes')
            ->relationship('baseVariationTypes')
            ->label('Varianti Base')
            ->helperText('Queste varianti definiscono la struttura di prezzo del prodotto (es. Spessore: 5mm, 10mm; Modello roll-up). Ogni opzione può avere le proprie foto.')
            ->defaultItems(0)
            ->reorderable(true)
            ->orderColumn('sort_order')
            ->schema([
                Hidden::make('is_modifier')->default(false),
                Select::make('variation_type_id')
                    ->label('Tipo di variante (es. Spessore, Modello)')
                    ->relationship('type', 'name')
                    ->required()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                Toggle::make('has_images')
                    ->label('Variante Visiva (cambia le immagini del prodotto)')
                    ->helperText('Attiva se ogni opzione ha un aspetto visivo diverso (es. colore, materiale).'),
                TextInput::make('sort_order')
                    ->label('Ordinamento')
                    ->numeric()
                    ->default(0)
                    ->columnSpan(1),
                Section::make('Immagini per Opzione')
                    ->description('Carica immagini specifiche per ogni opzione di questa variante (es. foto per Forex 5mm, foto diverse per Forex 10mm).')
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('optionImages')
                            ->relationship('options')
                            ->label('Galleria per Opzione')
                            ->defaultItems(0)
                            ->schema([
                                Select::make('variation_option_id')
                                    ->label('Opzione')
                                    ->options(function (Get $get) {
                                        $variationTypeId = $get('../../variation_type_id');
                                        if (! $variationTypeId) {
                                            return [];
                                        }

                                        return VariationOption::where('variation_type_id', $variationTypeId)->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                            ])
                            ->addActionLabel('Aggiungi Opzione')
                            ->reorderable(false),
                    ]),
            ])
            ->collapsible()
            ->itemLabel(function (array $state): ?string {
                /** @var VariationType|null $type */
                $type = VariationType::find($state['variation_type_id'] ?? null);

                return $type ? $type->name : null;
            })
            ->addActionLabel('Aggiungi Variante Base');
    }

    /**
     * MODIFIERS repeater (is_modifier = true).
     *
     * These add a flat (€/pezzo) or percentage surcharge on the base price.
     * Each option inherits its modifier from the global VariationOption default;
     * the admin can override the value per-product by entering a custom amount.
     * Leaving the field blank (null) resets to the global default.
     */
    public static function getModifiersRepeater(): Repeater
    {
        return Repeater::make('modifierVariationTypes')
            ->relationship('modifierVariationTypes')
            ->label('Modificatori di Prezzo')
            ->helperText('Questi aggiungono un sovrapprezzo al totale (es. Stampa Fronte+Retro +25%, Plastificazione +10%). Il valore di default è globale; qui puoi sovrascriverlo per questo prodotto.')
            ->defaultItems(0)
            ->reorderable(true)
            ->orderColumn('sort_order')
            ->schema([
                Hidden::make('is_modifier')->default(true),
                Select::make('variation_type_id')
                    ->label('Tipo di modificatore (es. Lati di Stampa, Finitura)')
                    ->relationship('type', 'name')
                    ->required()
                    ->live()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                TextInput::make('sort_order')
                    ->label('Ordinamento')
                    ->numeric()
                    ->default(0),
                Repeater::make('options')
                    ->relationship('options')
                    ->label('Opzioni e Sovrapprezzi')
                    ->defaultItems(0)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('variation_option_id')
                            ->label('Opzione')
                            ->options(function (Get $get) {
                                $variationTypeId = $get('../../variation_type_id');
                                if (! $variationTypeId) {
                                    return [];
                                }

                                return VariationOption::where('variation_type_id', $variationTypeId)->pluck('name', 'id');
                            })
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    /** @var VariationOption|null $option */
                                    $option = VariationOption::find($state);
                                    if ($option) {
                                        // Pre-fill with global default (user can override)
                                        // @phpstan-ignore-next-line the vendor stub declares the wrong return type for VariationOption::default_modifier_type
                                        $set('modifier_type', $option->default_modifier_type?->value ?? 'flat');
                                        $set('price_modifier', $option->default_price_modifier > 0 ? (float) $option->default_price_modifier : null);
                                    }
                                }
                            })
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                        Select::make('modifier_type')
                            ->label('Tipo di ricarico')
                            ->options(ModifierType::class)
                            ->default(ModifierType::Flat->value)
                            ->required(),
                        TextInput::make('price_modifier')
                            ->label('Valore (null = usa default globale)')
                            ->helperText(function (Get $get): ?string {
                                $optionId = $get('variation_option_id');
                                if (! $optionId) {
                                    return null;
                                }

                                /** @var VariationOption|null $option */
                                $option = VariationOption::find($optionId);
                                if (! $option || $option->default_price_modifier <= 0) {
                                    return 'Nessun default globale impostato.';
                                }

                                $modifierType = $option->default_modifier_type;

                                return "Default globale: {$option->default_price_modifier} ({$modifierType->getLabel()})";
                            })
                            ->numeric()
                            ->placeholder('Lascia vuoto per usare il default globale')
                            ->nullable(),
                    ])
                    ->columns(3)
                    ->addActionLabel('Aggiungi Opzione'),
            ])
            ->collapsible()
            ->itemLabel(function (array $state): ?string {
                /** @var VariationType|null $type */
                $type = VariationType::find($state['variation_type_id'] ?? null);

                return $type ? $type->name : null;
            })
            ->addActionLabel('Aggiungi Modificatore');
    }

    /**
     * Configures the repeater for Quantity-based discount tiers (Prezzi a Scaglioni).
     */
    public static function getPricingTiersRepeater(): Repeater
    {
        return Repeater::make('pricingTiers')
            ->relationship('pricingTiers')
            ->defaultItems(0)
            ->label('Prezzi a Scaglioni (Sconti per Quantità)')
            ->schema([
                TextInput::make('min_quantity')
                    ->label('Quantità')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                        $qty = (int) $state;
                        $unit = (float) $get('price_per_unit');
                        if ($qty > 0 && $unit > 0) {
                            $set('total_price', round($qty * $unit, 2));
                        }
                    }),
                Toggle::make('is_custom_price')
                    ->label('Prezzo bloccato / Sovrascritto')
                    ->default(false)
                    ->live(),
                TextInput::make('total_price')
                    ->label('Prezzo Totale (€)')
                    ->numeric()
                    ->prefix('€')
                    ->dehydrated(false)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, $state, Component $component) {
                        $qty = (int) $get('min_quantity');
                        if ($qty > 0 && is_numeric($state)) {
                            $unitPrice = (float) $state / $qty;
                            $set('price_per_unit', round($unitPrice, 4));
                            $set('is_custom_price', true);

                            self::cascadePricingTiers($component);
                        }
                    })
                    ->afterStateHydrated(function (TextInput $component, Get $get, Set $set) {
                        $qty = (int) $get('min_quantity');
                        $unit = (float) $get('price_per_unit');
                        if ($qty > 0 && $unit > 0) {
                            $set('total_price', round($qty * $unit, 2));
                        }
                    }),
                TextInput::make('price_per_unit')
                    ->label('Prezzo Unitario (€)')
                    ->numeric()
                    ->prefix('€')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, $state, Component $component) {
                        $qty = (int) $get('min_quantity');
                        $unit = (float) $state;
                        if ($qty > 0 && $unit > 0) {
                            $set('total_price', round($qty * $unit, 2));
                            $set('is_custom_price', true);

                            self::cascadePricingTiers($component);
                        }
                    }),
            ])
            ->columns(2)
            ->grid(1)
            ->addActionLabel('Aggiungi Scaglione di Prezzo');
    }

    private static function cascadePricingTiers(Component $component): void
    {
        $repeater = $component->getContainer()->getParentComponent();
        if (! $repeater instanceof Repeater) {
            return;
        }

        $statePath = $repeater->getStatePath();
        $livewire = $component->getLivewire();
        $tiers = data_get($livewire, $statePath);

        if (! is_array($tiers) || count($tiers) < 2) {
            return;
        }

        if (is_string($statePath)) {
            data_set($livewire, $statePath, PricingTierCascade::cascade($tiers));
        }
    }
}
