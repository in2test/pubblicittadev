<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products;

use App\Enums\ModifierType;
use App\Models\VariationOption;
use App\Models\VariationType;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

final class ProductResourceBaseVariations
{
    /**
     * Returns the repeater for base variations and options.
     *
     * This schema handles defining variation types (e.g., Color, Size) and their
     * specific options. It includes complex logic to determine how variations impact
     * the base price (modifiers vs redefining SKU prices).
     */
    public static function make(): Repeater
    {
        return Repeater::make('productVariationTypes')
            ->relationship('productVariationTypes')
            ->orderColumn('sort_order')
            ->reorderable(true)
            ->label('Varianti Prodotto')
            ->helperText('Configura le varianti e definisci come ciascuna influisce sul prezzo.')
            ->defaultItems(0)
            ->schema([
                self::variationTypeField(),
                Toggle::make('has_images')
                    ->label('Questa variante influenza le immagini (es. Colore)'),
                self::impactTypeField(),
                Hidden::make('is_modifier')->default(false),
                self::optionsRepeater(),
            ])
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => self::variationTypeLabel($state))
            ->addActionLabel('Aggiungi Variante');
    }

    private static function variationTypeField(): Select
    {
        return Select::make('variation_type_id')
            ->label('Tipo Variante')
            ->relationship('type', 'name')
            ->required()
            ->live()
            ->afterStateUpdated(function (Set $set, $state): void {
                /** @var VariationType|null $type */
                $type = $state ? VariationType::find($state) : null;

                if ($type && $type->default_modifier_type) {
                    $set('impact_type', $type->default_modifier_type);
                    $set('is_modifier', $type->default_modifier_type !== 'redefine');
                }
            })
            ->createOptionForm([
                TextInput::make('name')->required(),
                Select::make('presentation_type')
                    ->options([
                        'color_swatch' => 'Colore / Swatch',
                        'select' => 'Dropdown / Select',
                        'button' => 'Bottone / Scelta Singola',
                        'dimensions' => 'Dimensioni / Formato',
                    ])
                    ->default('select')
                    ->required(),
                Select::make('default_modifier_type')
                    ->label('Impatto sul Prezzo di Default')
                    ->options([
                        'redefine' => 'Nuovi prezzi / scaglioni (Crea SKU)',
                        'flat' => 'Aggiunta fissa (+€)',
                        'percentage' => 'Percentuale (+%)',
                    ])
                    ->default('redefine')
                    ->required(),
            ])
            ->disableOptionsWhenSelectedInSiblingRepeaterItems();
    }

    private static function impactTypeField(): Select
    {
        return Select::make('impact_type')
            ->label('Impatto sul Prezzo')
            ->options([
                'redefine' => 'Nuovi prezzi / scaglioni (Crea SKU)',
                'flat' => 'Aggiunta fissa (+€)',
                'percentage' => 'Percentuale (+%)',
            ])
            ->default('redefine')
            ->required()
            ->live()
            ->dehydrated(false)
            ->afterStateHydrated(function (Set $set, $state, $record): void {
                if (! $record) {
                    return;
                }

                if (! $record->is_modifier) {
                    $set('impact_type', 'redefine');

                    return;
                }

                $firstOption = $record->options()->first();
                $impactType = $firstOption && $firstOption->modifier_type === ModifierType::Percentage
                    ? 'percentage'
                    : 'flat';

                $set('impact_type', $impactType);
            })
            ->afterStateUpdated(function (Set $set, $state): void {
                $set('is_modifier', $state !== 'redefine');
            });
    }

    private static function optionsRepeater(): Repeater
    {
        return Repeater::make('options')
            ->relationship('options')
            ->orderColumn('sort_order')
            ->reorderable(true)
            ->label('Opzioni per questa Variante')
            ->schema([
                self::variationOptionField(),
                self::priceModifierField(),
                Hidden::make('modifier_type')
                    ->default(fn (Get $get): string => $get('../../impact_type') === 'percentage'
                        ? ModifierType::Percentage->value
                        : ModifierType::Flat->value),
            ])
            ->columns(2)
            ->addActionLabel('Aggiungi Opzione')
            ->columnSpanFull();
    }

    private static function variationOptionField(): Select
    {
        return Select::make('variation_option_id')
            ->label('Opzione')
            ->options(fn (Get $get) => $get('../../variation_type_id')
                ? VariationOption::where('variation_type_id', $get('../../variation_type_id'))->pluck('name', 'id')
                : [])
            ->required()
            ->live()
            ->createOptionForm(fn (Get $get): array => self::createVariationOptionForm($get))
            ->createOptionUsing(fn (array $data, Get $get) => VariationOption::create([
                'variation_type_id' => $get('../../variation_type_id'),
                'name' => $data['name'],
                'value' => $data['name'],
                'default_modifier_type' => $data['default_modifier_type'] ?? 'flat',
                'default_price_modifier' => $data['default_price_modifier'] ?? 0.00,
                'width' => $data['width'] ?? null,
                'height' => $data['height'] ?? null,
                'color_hex' => $data['color_hex'] ?? null,
            ])->id)
            ->disableOptionsWhenSelectedInSiblingRepeaterItems();
    }

    /**
     * @return array<int, Component>
     */
    private static function createVariationOptionForm(Get $get): array
    {
        /** @var VariationType|null $type */
        $type = $get('../../variation_type_id') ? VariationType::find($get('../../variation_type_id')) : null;
        $isDimensions = $type?->presentation_type === 'dimensions';
        $isModifier = in_array($type?->default_modifier_type, ['flat', 'percentage']);
        $defaultType = ($isModifier && $type) ? $type->default_modifier_type : 'flat';

        return [
            TextInput::make('name')
                ->label('Nome Opzione')
                ->required(),
            TextInput::make('width')
                ->label('Larghezza (mm)')
                ->numeric()
                ->visible($isDimensions),
            TextInput::make('height')
                ->label('Lunghezza (mm)')
                ->numeric()
                ->visible($isDimensions),
            Select::make('default_modifier_type')
                ->label('Tipo Impatto Prezzo')
                ->options([
                    'flat' => 'Importo Fisso (€)',
                    'percentage' => 'Percentuale (%)',
                ])
                ->default($defaultType)
                ->visible($isModifier),
            TextInput::make('default_price_modifier')
                ->label('Sovrapprezzo di Default')
                ->numeric()
                ->placeholder('es. 10 per 10% o 1.50 per €1.50')
                ->visible($isModifier),
            TextInput::make('color_hex')
                ->label('Hex Colore (es. #ff0000)')
                ->visible($type?->presentation_type === 'color_swatch'),
        ];
    }

    private static function priceModifierField(): TextInput
    {
        return TextInput::make('price_modifier')
            ->label(fn (Get $get): string => $get('../../impact_type') === 'percentage' ? 'Sovrapprezzo (%)' : 'Aggiunta (€)')
            ->numeric()
            ->prefix(fn (Get $get): ?string => $get('../../impact_type') === 'flat' ? '€' : null)
            ->suffix(fn (Get $get): ?string => $get('../../impact_type') === 'percentage' ? '%' : null)
            ->placeholder('Usa default globale')
            ->nullable()
            ->helperText(fn (Get $get): ?string => self::defaultModifierHelperText($get))
            ->visible(fn (Get $get): bool => in_array($get('../../impact_type'), ['flat', 'percentage']));
    }

    private static function defaultModifierHelperText(Get $get): ?string
    {
        $optionId = $get('variation_option_id');
        if (! $optionId) {
            return null;
        }

        /** @var VariationOption|null $option */
        $option = VariationOption::find($optionId);
        if (! $option || ! $option->default_price_modifier) {
            return 'Nessun default globale impostato.';
        }

        $symbol = $option->default_modifier_type === ModifierType::Percentage ? '%' : '€';

        return "Default globale: {$option->default_price_modifier}{$symbol}";
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private static function variationTypeLabel(array $state): ?string
    {
        /** @var VariationType|null $type */
        $type = VariationType::find($state['variation_type_id'] ?? null);

        return $type?->name;
    }
}
