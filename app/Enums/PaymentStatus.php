<?php

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
}
