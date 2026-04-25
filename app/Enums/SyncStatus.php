<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SyncStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Syncing = 'syncing';
    case Synced = 'synced';
    case Failed = 'failed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'In Attesa',
            self::Syncing => 'In Sincronizzazione',
            self::Synced => 'Sincronizzato',
            self::Failed => 'Fallito',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Syncing => 'warning',
            self::Synced => 'success',
            self::Failed => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pending => 'heroicon-m-clock',
            self::Syncing => 'heroicon-m-arrow-path',
            self::Synced => 'heroicon-m-check-badge',
            self::Failed => 'heroicon-m-exclamation-circle',
        };
    }
}
