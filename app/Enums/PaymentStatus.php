<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    /**
     * Payment has not yet been received or confirmed.
     */
    case Pending = 'pending';

    /**
     * Payment has been successfully processed.
     */
    case Paid = 'paid';

    /**
     * Payment was cancelled or failed.
     */
    case Cancelled = 'cancelled';

    /**
     * The order is a quote and has not been paid yet.
     */
    case Quotation = 'quotation';

    public function weight(): int
    {
        return match ($this) {
            self::Pending => 0,
            self::Quotation => 1,
            self::Cancelled => 2,
            self::Paid => 3,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In Attesa',
            self::Paid => 'Pagato',
            self::Cancelled => 'Annullato',
            self::Quotation => 'Preventivo',
        };
    }
}
