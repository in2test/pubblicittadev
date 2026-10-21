<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SyncLevel: string implements HasColor, HasIcon, HasLabel
{
    case Full = 'full';
    case Meta = 'meta';
    case Availability = 'availability';

    public function getLabel(): string
    {
        return match ($this) {
            self::Full => 'Sincronizzazione completa',
            self::Meta => 'Solo metadati',
            self::Availability => 'Solo disponibilità',
        };
    }

    /**
     * Short description of what the level refreshes.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::Full => 'Aggiorna metadati, immagini, varianti, prezzi e disponibilità.',
            self::Meta => 'Aggiorna solo nome, prezzo, descrizione, schede tecniche, certificazioni e istruzioni di lavaggio.',
            self::Availability => 'Aggiorna solo le quantità e la disponibilità degli SKU.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Full => 'success',
            self::Meta => 'info',
            self::Availability => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Full => 'heroicon-m-arrow-path',
            self::Meta => 'heroicon-m-document-text',
            self::Availability => 'heroicon-m-cube',
        };
    }
}
