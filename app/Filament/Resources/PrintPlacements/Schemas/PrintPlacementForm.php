<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrintPlacements\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PrintPlacementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dettagli Posizione di Stampa')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome Posizione (es. Manica Sinistra, Fronte)')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('description')
                            ->label('Descrizione / Note')
                            ->maxLength(255),
                        TextInput::make('default_price')
                            ->label('Prezzo di Default (€)')
                            ->numeric()
                            ->prefix('€')
                            ->default(0)
                            ->required(),
                        TextInput::make('sort_order')
                            ->label('Ordinamento')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        FileUpload::make('template_path')
                            ->label('File Template (es. PDF, PSD, AI)')
                            ->disk('public')
                            ->visibility('public')
                            ->directory('templates/placements'),
                    ]),
            ]);
    }
}
