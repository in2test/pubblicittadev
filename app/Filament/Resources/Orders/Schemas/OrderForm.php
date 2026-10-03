<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\PaymentStatus;
use App\Enums\WorkStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                self::orderDetailsSection(),
                self::customerAddressesSection(),
                self::stripeInfoSection(),
                self::orderNotesSection(),
                self::shippingSection(),
                self::invoiceSection(),
                self::itemsSection(),
            ]);
    }

    private static function orderDetailsSection(): Section
    {
        return Section::make('Dettagli Ordine')
            ->schema([
                Grid::make(3)->schema([
                    TextInput::make('order_number')
                        ->label('Numero Ordine')
                        ->disabled()
                        ->required(),
                    Select::make('payment_status')
                        ->label('Stato Pagamento')
                        ->options(PaymentStatus::class)
                        ->required(),
                    Select::make('work_status')
                        ->label('Stato Lavorazione (Automatico)')
                        ->options(WorkStatus::class)
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
            ]);
    }

    private static function customerAddressesSection(): Section
    {
        return Section::make('Cliente & Indirizzi')
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
            ]);
    }

    private static function stripeInfoSection(): Section
    {
        return Section::make('Stripe Info')
            ->collapsed()
            ->schema([
                TextInput::make('stripe_session_id')
                    ->label('Session ID')
                    ->disabled(),
                TextInput::make('stripe_payment_intent_id')
                    ->label('Payment Intent ID')
                    ->disabled(),
            ]);
    }

    private static function orderNotesSection(): Section
    {
        return Section::make('Note Ordine Generale')
            ->schema([
                Textarea::make('notes')
                    ->label('Note Cliente (Inserite al Checkout)')
                    ->columnSpanFull()
                    ->disabled(),
            ]);
    }

    private static function shippingSection(): Section
    {
        return Section::make('Spedizione (Tracking)')
            ->schema([
                Grid::make(3)->schema([
                    Select::make('transporter_id')
                        ->relationship('transporter', 'name')
                        ->label('Corriere Predefinito'),
                    TextInput::make('tracking_code')
                        ->label('Codice di Tracciamento')
                        ->maxLength(255),
                    TextInput::make('tracking_url')
                        ->label('Link di Tracciamento Diretto (Opzionale)')
                        ->url()
                        ->maxLength(255)
                        ->helperText('Ignora il corriere/codice e usa direttamente questo link URL.'),
                ]),
            ]);
    }

    private static function invoiceSection(): Section
    {
        return Section::make('Fatturazione (PDF)')
            ->schema([
                SpatieMediaLibraryFileUpload::make('invoice')
                    ->collection('invoices')
                    ->label('Carica Fattura PDF')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(5120)
                    ->downloadable()
                    ->columnSpanFull(),
            ]);
    }

    private static function itemsSection(): Section
    {
        return Section::make('Lavorazioni (Items)')
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
                            self::unitPriceField(),
                            self::subtotalField(),
                            Select::make('work_status')
                                ->label('Stato Lavorazione')
                                ->options(WorkStatus::class)
                                ->required(),
                            OrderItemCustomizationDetails::field(),
                        ]),
                    ])
                    ->disableItemCreation()
                    ->disableItemDeletion()
                    ->disableItemMovement()
                    ->columnSpanFull(),
            ]);
    }

    private static function unitPriceField(): TextInput
    {
        return TextInput::make('unit_price')
            ->label('Prezzo Unitario')
            ->numeric()
            ->prefix('€')
            ->live(onBlur: true)
            ->afterStateUpdated(function (Get $get, Set $set) {
                $qty = (int) (string) $get('quantity');
                $unit = (float) (string) $get('unit_price');
                $subtotal = round($qty * $unit, 2);
                $set('subtotal', $subtotal);

                self::updateOrderTotal($get, $set);
            });
    }

    private static function subtotalField(): TextInput
    {
        return TextInput::make('subtotal')
            ->label('Subtotale')
            ->numeric()
            ->prefix('€')
            ->live(onBlur: true)
            ->afterStateUpdated(function (Get $get, Set $set) {
                $qty = (int) (string) $get('quantity');
                $subtotal = (float) (string) $get('subtotal');
                if ($qty > 0) {
                    $set('unit_price', round($subtotal / $qty, 2));
                }

                self::updateOrderTotal($get, $set);
            });
    }

    private static function updateOrderTotal(Get $get, Set $set): void
    {
        /** @var array<int|string, array<string, mixed>> $items */
        $items = $get('../../items') ?? [];
        $total = collect($items)->sum(fn ($item) => (float) (string) ($item['subtotal'] ?? 0));
        $set('../../total_price', $total);
    }
}
