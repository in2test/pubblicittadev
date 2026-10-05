<?php

declare(strict_types=1);

namespace App\Filament\Resources\Campaigns\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome campagna')
                    ->required()
                    ->maxLength(255),
                DateTimePicker::make('starts_at')
                    ->label('Inizio')
                    ->required()
                    ->locale('it')
                    ->displayFormat('d/m/Y H:i')
                    ->native()
                    ->timezone('Europe/Rome'),

                DateTimePicker::make('ends_at')
                    ->label('Fine')
                    ->required()
                    ->locale('it')
                    ->displayFormat('d/m/Y H:i')
                    ->native()
                    ->after('starts_at')
                    ->timezone('Europe/Rome'),
            ]);
    }
}
