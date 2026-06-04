<?php
declare(strict_types=1);
return [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'api_version' => '2026-05-27.dahlia',
];
