<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                                ->label('Stato Lavorazione')
                                ->options([
                                    'pending' => 'In Attesa',
                                    'processing' => 'In Lavorazione',
                                    'ready' => 'Pronto per Spedizione',
                                    'shipped' => 'Spedito',
                                    'completed' => 'Completato',
                                ])
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
                    ->columnSpanFull(),
            ]);
    }
}
