<?php

declare(strict_types=1);

namespace App\Enums;

enum WorkStatus: string
{
    /**
     * Waiting for files from client.
     */
    case Pending = 'pending';

    /**
     * Waiting to receive files from customer.
     */
    case AwaitingFile = 'awaiting_file';

    /**
     * Order is being processed/working on the order.
     */
    case Processing = 'processing';

    /**
     * Ready to be shipped or delivered.
     */
    case Ready = 'ready';

    /**
     * Order has been dispatched by courier.
     */
    case Shipped = 'shipped';

    /**
     * The order has been delivered and finalized.
     */
    case Completed = 'completed';

    public function weight(): int
    {
        return match ($this) {
            self::Pending => 0,
            self::AwaitingFile => 1,
            self::Processing => 2,
            self::Ready => 3,
            self::Shipped => 4,
            self::Completed => 5,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In Attesa',
            self::AwaitingFile => 'Attendiamo File',
            self::Processing => 'In Lavorazione',
            self::Ready => 'Pronto per Spedizione',
            self::Shipped => 'Spedito',
            self::Completed => 'Completato',
        };
    }

    /** @phpstan-ignore-next-line match expression with array return is a false positive */
    public static function labels(): array
    {
        return [
            self::Pending->value => 'In Attesa',
            self::AwaitingFile->value => 'Attendiamo File',
            self::Processing->value => 'In Lavorazione',
            self::Ready->value => 'Pronto per Spedizione',
            self::Shipped->value => 'Spedito',
            self::Completed->value => 'Completato',
        ];
    }
}
