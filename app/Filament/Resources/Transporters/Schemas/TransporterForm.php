<?php

declare(strict_types=1);

namespace App\Filament\Resources\Transporters\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TransporterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('tracking_url_template'),
            ]);
    }
}
