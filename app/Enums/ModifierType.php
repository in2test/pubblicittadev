<?php

declare(strict_types=1);

namespace App\Enums;

enum ModifierType: string
{
    case Flat = 'flat';
    case Percentage = 'percentage';

    public function getLabel(): string
    {
        return match ($this) {
            self::Flat => 'Fisso (€ a pezzo)',
            self::Percentage => 'Percentuale (%)',
        };
    }
}
