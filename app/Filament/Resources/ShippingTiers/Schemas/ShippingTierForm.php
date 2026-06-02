<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShippingTiers\Schemas;

use Filament\Schemas\Schema;

class ShippingTierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
