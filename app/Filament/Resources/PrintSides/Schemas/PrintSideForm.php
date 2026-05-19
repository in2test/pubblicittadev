<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrintSides\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PrintSideForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dettagli Lato di Stampa')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome Lato (es. Fronte e retro uguali, Stampa sul fronte)')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('description')
                            ->label('Descrizione / Note')
                            ->maxLength(255),
                        TextInput::make('sort_order')
                            ->label('Ordinamento')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        FileUpload::make('template_path')
                            ->label('File Template (es. PDF, PSD, AI)')
                            ->disk('public')
                            ->visibility('public')
                            ->directory('templates/sides'),
                    ]),
            ]);
    }
}
