<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
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
                                ->required()
                                ->unique(ignoreRecord: true),
                            Select::make('status')
                                ->options([
                                    'pending' => 'In Attesa',
                                    'paid' => 'Pagato',
                                    'failed' => 'Fallito',
                                    'cancelled' => 'Annullato',
                                    'completed' => 'Completato',
                                ])
                                ->required()
                                ->default('pending'),
                            DateTimePicker::make('paid_at'),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('user_id')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->required(),
                            Select::make('quote_id')
                                ->relationship('quote', 'quote_number')
                                ->searchable()
                                ->placeholder('Opzionale'),
                        ]),
                    ]),

                Section::make('Indirizzi')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('shipping_address_id')
                                ->relationship('shippingAddress', 'name')
                                ->searchable(),
                            Select::make('billing_address_id')
                                ->relationship('billingAddress', 'name')
                                ->searchable(),
                        ]),
                    ])->collapsible(),

                Section::make('Stripe')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('stripe_session_id')
                                ->disabled(),
                            TextInput::make('stripe_payment_intent_id')
                                ->disabled(),
                        ]),
                    ])->collapsible(),

                Section::make('Articoli Ordine')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->required()
                                    ->columnSpan(2),
                                Select::make('color_id')
                                    ->relationship('color', 'color_name')
                                    ->columnSpan(1),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan(1),
                                TextInput::make('unit_price')
                                    ->numeric()
                                    ->required()
                                    ->prefix('€')
                                    ->columnSpan(1),
                                TextInput::make('subtotal')
                                    ->numeric()
                                    ->required()
                                    ->prefix('€')
                                    ->columnSpan(1),
                                Textarea::make('customization_json')
                                    ->json()
                                    ->columnSpanFull(),
                            ])
                            ->columns(6)
                            ->columnSpanFull(),

                        Grid::make(2)->schema([
                            TextInput::make('total_items')
                                ->required()
                                ->numeric(),
                            TextInput::make('total_price')
                                ->required()
                                ->numeric()
                                ->prefix('€'),
                        ]),
                    ]),

                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
