<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\ProductClass;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\VariationOption;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dettagli Ordine')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('order_number')
                                ->label('Numero Ordine')
                                ->disabled()
                                ->required(),
                            Select::make('payment_status')
                                ->label('Stato Pagamento')
                                ->options([
                                    'pending' => 'In Attesa',
                                    'paid' => 'Pagato',
                                    'cancelled' => 'Annullato',
                                ])
                                ->required(),
                            Select::make('work_status')
                                ->label('Stato Lavorazione (Automatico)')
                                ->options([
                                    'pending' => 'In Attesa',
                                    'awaiting_file' => 'Attendiamo File',
                                    'processing' => 'In Lavorazione',
                                    'ready' => 'Pronto per Spedizione',
                                    'shipped' => 'Spedito',
                                    'completed' => 'Completato',
                                ])
                                ->disabled()
                                ->required(),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('total_price')
                                ->label('Totale')
                                ->numeric()
                                ->prefix('€')
                                ->disabled()
                                ->dehydrated(),
                            TextInput::make('total_items')
                                ->label('Articoli Totali')
                                ->numeric()
                                ->disabled(),
                            DateTimePicker::make('paid_at')
                                ->label('Pagato il')
                                ->disabled(),
                        ]),
                    ]),

                Section::make('Cliente & Indirizzi')
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('Cliente')
                            ->disabled()
                            ->required(),
                        Grid::make(2)->schema([
                            Select::make('shipping_address_id')
                                ->relationship('shippingAddress', 'name')
                                ->label('Indirizzo Spedizione')
                                ->disabled(),
                            Select::make('billing_address_id')
                                ->relationship('billingAddress', 'name')
                                ->label('Indirizzo Fatturazione')
                                ->disabled(),
                        ]),
                    ]),

                Section::make('Stripe Info')
                    ->collapsed()
                    ->schema([
                        TextInput::make('stripe_session_id')
                            ->label('Session ID')
                            ->disabled(),
                        TextInput::make('stripe_payment_intent_id')
                            ->label('Payment Intent ID')
                            ->disabled(),
                    ]),

                Textarea::make('notes')
                    ->label('Note Cliente')
                    ->columnSpanFull()
                    ->disabled(),

                Section::make('Lavorazioni (Items)')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->relationship(modifyQueryUsing: fn (Builder $query) => $query->with('product'))
                            ->schema([
                                Grid::make(5)->schema([
                                    Select::make('product_id')
                                        ->relationship('product', 'name')
                                        ->label('Prodotto')
                                        ->disabled(),
                                    TextInput::make('quantity')
                                        ->label('Quantità')
                                        ->disabled(),
                                    TextInput::make('unit_price')
                                        ->label('Prezzo Unitario')
                                        ->numeric()
                                        ->prefix('€')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            $qty = (int) $get('quantity');
                                            $unit = (float) $get('unit_price');
                                            $subtotal = round($qty * $unit, 2);
                                            $set('subtotal', $subtotal);

                                            $items = $get('../../items') ?? [];
                                            $total = collect($items)->sum(fn ($item) => (float) ($item['subtotal'] ?? 0));
                                            $set('../../total_price', $total);
                                        }),
                                    TextInput::make('subtotal')
                                        ->label('Subtotale')
                                        ->numeric()
                                        ->prefix('€')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            $qty = (int) $get('quantity');
                                            $subtotal = (float) $get('subtotal');
                                            if ($qty > 0) {
                                                $set('unit_price', round($subtotal / $qty, 2));
                                            }

                                            $items = $get('../../items') ?? [];
                                            $total = collect($items)->sum(fn ($item) => (float) ($item['subtotal'] ?? 0));
                                            $set('../../total_price', $total);
                                        }),
                                    Select::make('work_status')
                                        ->label('Stato Lavorazione')
                                        ->options([
                                            'pending' => 'In Attesa',
                                            'awaiting_file' => 'Attendiamo File',
                                            'processing' => 'In Lavorazione',
                                            'ready' => 'Pronto per Spedizione',
                                            'shipped' => 'Spedito',
                                            'completed' => 'Completato',
                                        ])
                                        ->required(),
                                    Placeholder::make('customization_details')
                                        ->label('Dettagli Lavorazione / Note')
                                        ->content(function ($record): string|HtmlString {
                                            if (! $record || ! $record->customization_json) {
                                                return '-';
                                            }
                                            $json = $record->customization_json;
                                            $html = '<ul class="list-disc pl-4 space-y-1 text-sm">';
                                            $qty = $record->quantity ?? 1;

                                            // Options Summary & Thickness Parsing
                                            $thicknessMm = 0;
                                            if (! empty($json['options_summary'])) {
                                                foreach ($json['options_summary'] as $key => $val) {
                                                    $html .= "<li><strong>{$key}:</strong> {$val}</li>";

                                                    // Parse thickness/depth for package size
                                                    $combined = strtolower($key.' '.$val);
                                                    if (str_contains($combined, 'spessore') || str_contains($combined, 'profondit') || str_contains($combined, 'telaio')) {
                                                        if (preg_match('/([0-9.,]+)\s*(mm|cm)/i', $combined, $matches)) {
                                                            $valNum = (float) str_replace(',', '.', $matches[1]);
                                                            if (strtolower($matches[2]) === 'cm') {
                                                                $valNum *= 10;
                                                            }
                                                            $thicknessMm = $valNum;
                                                        }
                                                    }
                                                }
                                            }

                                            // Dimensions & Advanced Calculations
                                            $width = isset($json['width']) ? (float) $json['width'] : 0;
                                            $height = isset($json['height']) ? (float) $json['height'] : 0;
                                            $product = $record->relationLoaded('product') ? $record->product : Product::find($record->product_id);

                                            if ($width > 0 && $height > 0) {
                                                $html .= "<li><strong>Dimensioni singole:</strong> {$width} x {$height} mm</li>";

                                                // Area Totale
                                                $totalSqm = ($width * $height) / 1000000.0 * $qty;
                                                $html .= '<li><strong>Area Totale:</strong> '.number_format($totalSqm, 2, ',', '.').' mq</li>';

                                                // Sheets Impagination
                                                if ($product && $product->sheet_width > 0 && $product->sheet_height > 0) {
                                                    $sw = (float) $product->sheet_width;
                                                    $sh = (float) $product->sheet_height;

                                                    $fit1 = floor($sw / $width) * floor($sh / $height);
                                                    $fit2 = floor($sw / $height) * floor($sh / $width);
                                                    $itemsPerSheet = max($fit1, $fit2);

                                                    if ($itemsPerSheet > 0) {
                                                        $sheetsNeeded = ceil($qty / $itemsPerSheet);
                                                        $html .= "<li><strong>Impaginazione:</strong> {$itemsPerSheet} pz per lastra ({$sw}x{$sh}mm). Totale lastre necessarie: {$sheetsNeeded}</li>";
                                                    }
                                                }

                                                // Package Size
                                                if ($thicknessMm > 0) {
                                                    $packDepth = $thicknessMm * $qty;
                                                    $html .= "<li><strong>Ingombro Pacco Stimato:</strong> {$width} x {$height} x {$packDepth} mm</li>";
                                                }
                                            }

                                            // Quantities breakdown (hide only for single-sku Area products to avoid duplication)
                                            $showQuantities = true;
                                            if (! empty($json['quantities']) && count($json['quantities']) === 1) {
                                                if (isset($product) && $product->product_class === ProductClass::AreaBased) {
                                                    $showQuantities = false;
                                                }
                                            }

                                            if (! empty($json['quantities']) && $showQuantities) {
                                                $html .= '<li><strong>Taglie/Varianti:</strong> ';
                                                $html .= '<ul class="pl-4 mt-1 space-y-1">';
                                                foreach ($json['quantities'] as $skuId => $q) {
                                                    $sku = ProductSku::with('options.type')->find($skuId);
                                                    if ($sku && $sku->options->isNotEmpty()) {
                                                        $optionLabels = $sku->options->map(fn (VariationOption $opt) => ($opt->type ? $opt->type->getAttribute('name').': ' : '').$opt->getAttribute('name'))->join(', ');
                                                        $skuLabel = $optionLabels;
                                                    } else {
                                                        $skuLabel = "Variante #{$skuId}";
                                                    }
                                                    $html .= "<li>- {$skuLabel}: <strong>{$q} pz</strong></li>";
                                                }
                                                $html .= '</ul></li>';
                                            }

                                            // Item Notes
                                            if (! empty($json['notes'])) {
                                                $html .= "<li><strong>Note Cliente:</strong> <span class=\"text-red-600 font-bold\">{$json['notes']}</span></li>";
                                            }

                                            $html .= '</ul>';

                                            return new HtmlString($html);
                                        })
                                        ->columnSpanFull(),
                                ]),
                            ])
                            ->disableItemCreation()
                            ->disableItemDeletion()
                            ->disableItemMovement()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
