<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\NewsletterSubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $email
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Database\Factories\NewsletterSubscriptionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscription query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscription whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscription whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NewsletterSubscription whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[Fillable([
    'email',
    'is_active',
])]
class NewsletterSubscription extends Model
{
    /** @use HasFactory<NewsletterSubscriptionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
