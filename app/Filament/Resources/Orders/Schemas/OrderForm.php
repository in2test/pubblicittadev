<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

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
use Filament\Schemas\Schema;
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
                                ->disabled(),
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
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Grid::make(3)->schema([
                                    Select::make('product_id')
                                        ->relationship('product', 'name')
                                        ->label('Prodotto')
                                        ->disabled(),
                                    TextInput::make('quantity')
                                        ->label('Quantità')
                                        ->disabled(),
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

                                            // Options Summary
                                            if (! empty($json['options_summary'])) {
                                                foreach ($json['options_summary'] as $key => $val) {
                                                    $html .= "<li><strong>{$key}:</strong> {$val}</li>";
                                                }
                                            }

                                            // Dimensions
                                            if (! empty($json['width']) && ! empty($json['height'])) {
                                                $html .= "<li><strong>Dimensioni:</strong> {$json['width']} x {$json['height']} mm</li>";
                                            }

                                            // Quantities breakdown
                                            if (! empty($json['quantities'])) {
                                                $html .= '<li><strong>Taglie/Varianti:</strong> ';
                                                $html .= '<ul class="pl-4 mt-1 space-y-1">';
                                                foreach ($json['quantities'] as $skuId => $qty) {
                                                    $sku = ProductSku::with('options.type')->find($skuId);
                                                    if ($sku && $sku->options->isNotEmpty()) {
                                                        $optionLabels = $sku->options->map(fn (VariationOption $opt) => ($opt->type ? $opt->type->getAttribute('name').': ' : '').$opt->getAttribute('name'))->join(', ');
                                                        $skuLabel = $optionLabels;
                                                    } else {
                                                        $skuLabel = "Variante #{$skuId}";
                                                    }
                                                    $html .= "<li>- {$skuLabel}: <strong>{$qty} pz</strong></li>";
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
