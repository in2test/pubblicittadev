<?php

declare(strict_types=1);

namespace App\Filament\Resources\Campaigns\Schemas;

use App\Models\Campaign;
use App\Models\ProductSku;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dettagli Campagna')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome campagna')
                            ->required()
                            ->maxLength(255),
                        DateTimePicker::make('starts_at')
                            ->label('Inizio')
                            ->nullable()
                            ->helperText('Vuoto per attiva da subito')
                            ->locale('it')
                            ->displayFormat('d/m/Y H:i')
                            ->native()
                            ->timezone('Europe/Rome'),
                        DateTimePicker::make('ends_at')
                            ->label('Fine')
                            ->nullable()
                            ->helperText('Vuoto per attiva a tempo indeterminato')
                            ->locale('it')
                            ->displayFormat('d/m/Y H:i')
                            ->native()
                            ->after('starts_at')
                            ->timezone('Europe/Rome'),
                    ])
                    ->columns(3),

                Section::make('Associazione Rapida')
                    ->description('Puoi associare offerte e varianti outlet direttamente qui, oppure gestirle dettagliatamente dalla tabella prodotti.')
                    ->schema([
                        Select::make('products')
                            ->label('Prodotti in Offerta')
                            ->relationship(
                                'products',
                                'name',
                                modifyQueryUsing: fn (Builder $query, ?Model $record) => $query
                                    ->where('offer_price', '>', 0)
                                    ->where(function (Builder $q) use ($record): void {
                                        $q->whereDoesntHave('campaigns');
                                        if ($record instanceof Campaign && $record->exists) {
                                            $q->orWhereHas('campaigns', fn (Builder $cq) => $cq->where('campaigns.id', $record->id));
                                        }
                                    }),
                            )
                            ->multiple()
                            ->searchable()
                            ->preload(),

                        Select::make('outletSkus')
                            ->label('Varianti Outlet')
                            ->relationship(
                                'outletSkus',
                                'sku',
                                modifyQueryUsing: fn (Builder $query, ?Model $record) => $query
                                    ->with('product')
                                    ->where('is_outlet', true)
                                    ->where(function (Builder $q) use ($record): void {
                                        $q->whereDoesntHave('campaigns');
                                        if ($record instanceof Campaign && $record->exists) {
                                            $q->orWhereHas('campaigns', fn (Builder $cq) => $cq->where('campaigns.id', $record->id));
                                        }
                                    }),
                            )
                            ->getOptionLabelFromRecordUsing(fn (ProductSku $record): string => "{$record->product?->name} — {$record->sku}")
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),
            ]);
    }
}
