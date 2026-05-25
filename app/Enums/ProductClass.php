<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductClass: string
{
    case Apparel = 'apparel';
    case AreaBased = 'area_based';
    case ItemBased = 'item_based';

    public function getLabel(): string
    {
        return match ($this) {
            self::Apparel => 'Abbigliamento',
            self::AreaBased => 'Grande Formato / Superficie',
            self::ItemBased => 'Standard / A Pezzo',
        };
    }
}
