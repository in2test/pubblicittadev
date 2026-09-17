<?php

namespace App\Enums;

enum WorkStatus: string
{
    /**
     * Initial state, order is created but not yet being worked on.
     */
    case Pending = 'pending';

    /**
     * Order is waiting for the customer to upload required design files.
     */
    case AwaitingFile = 'awaiting_file';

    /**
     * The items in the order are currently being manufactured or prepared.
     */
    case Processing = 'processing';

    /**
     * The order is complete and ready to be shipped.
     */
    case Ready = 'ready';

    /**
     * The order has been handed over to the transporter.
     */
    case Shipped = 'shipped';

    /**
     * The order has been delivered and finalized.
     */
    case Completed = 'completed';
}
