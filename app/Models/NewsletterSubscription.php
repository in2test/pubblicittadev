<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\NewsletterSubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'email',
    'is_active',
])]
class NewsletterSubscription extends Model
{
    /** @use HasFactory<NewsletterSubscriptionFactory> */
    use HasFactory;

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
