<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\NewsletterSubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $email
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static NewsletterSubscriptionFactory factory($count = null, $state = [])
 * @method static Builder<static>|NewsletterSubscription newModelQuery()
 * @method static Builder<static>|NewsletterSubscription newQuery()
 * @method static Builder<static>|NewsletterSubscription query()
 * @method static Builder<static>|NewsletterSubscription whereCreatedAt($value)
 * @method static Builder<static>|NewsletterSubscription whereEmail($value)
 * @method static Builder<static>|NewsletterSubscription whereId($value)
 * @method static Builder<static>|NewsletterSubscription whereIsActive($value)
 * @method static Builder<static>|NewsletterSubscription whereUpdatedAt($value)
 *
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
