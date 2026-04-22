<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\StandardProducts\Schemas;

use Filament\Schemas\Schema;

class StandardProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
